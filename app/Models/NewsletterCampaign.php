<?php

namespace App\Models;

use App\Core\App;
use App\Services\MailService;
use PDO;
use Throwable;

class NewsletterCampaign extends BaseModel {
    public function all(): array {
        return $this->db->query("SELECT c.*,
            COUNT(r.id) recipients,SUM(r.status='sent') sent_count,SUM(r.opened_at IS NOT NULL) open_count,SUM(r.clicked_at IS NOT NULL) click_count
            FROM newsletter_campaigns c LEFT JOIN newsletter_campaign_recipients r ON r.campaign_id=c.id
            GROUP BY c.id ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data,int $adminId): array {
        $name=trim((string)($data['name']??''));$subject=trim((string)($data['subject']??''));$body=trim((string)($data['body']??''));
        $segment=(string)($data['segment']??'all');$target=trim((string)($data['target_url']??''));
        if($name===''||$subject===''||$body==='')return ['success'=>false,'message'=>'Tên, tiêu đề và nội dung chiến dịch là bắt buộc.'];
        if(!in_array($segment,['all','customers','prospects'],true))$segment='all';
        if ($target !== '' && !preg_match('#^https?://#i', $target)) {
            $target = App::publicUrl($target);
        }
        $html='<div style="font-family:Arial,sans-serif;max-width:680px;margin:auto"><h2>'.htmlspecialchars($subject,ENT_QUOTES,'UTF-8').'</h2><p>'.nl2br(htmlspecialchars($body,ENT_QUOTES,'UTF-8')).'</p></div>';
        $stmt=$this->db->prepare("INSERT INTO newsletter_campaigns(name,subject,body_html,target_url,segment,status,created_by) VALUES(?,?,?,?,?,'draft',?)");
        $stmt->execute([$name,$subject,$html,$target?:null,$segment,$adminId]);
        return ['success'=>true,'message'=>'Đã tạo bản nháp chiến dịch.','id'=>(int)$this->db->lastInsertId()];
    }

    public function queue(int $campaignId): array {
        try{
            $this->db->beginTransaction();
            $stmt=$this->db->prepare("SELECT * FROM newsletter_campaigns WHERE id=? AND status IN('draft','failed') FOR UPDATE");$stmt->execute([$campaignId]);$c=$stmt->fetch(PDO::FETCH_ASSOC);
            if(!$c)throw new \RuntimeException('Chiến dịch không ở trạng thái có thể xếp hàng.');
            $where="ns.status='subscribed'";
            if($c['segment']==='customers')$where.=' AND EXISTS(SELECT 1 FROM user u WHERE LOWER(u.email)=LOWER(ns.email))';
            if($c['segment']==='prospects')$where.=' AND NOT EXISTS(SELECT 1 FROM user u WHERE LOWER(u.email)=LOWER(ns.email))';
            $subs=$this->db->query("SELECT id,email FROM newsletter_subscriptions ns WHERE $where")->fetchAll(PDO::FETCH_ASSOC);
            $insert=$this->db->prepare("INSERT IGNORE INTO newsletter_campaign_recipients(campaign_id,subscription_id,email,open_token,click_token,status) VALUES(?,?,?,?,?,'pending')");
            foreach($subs as $s)$insert->execute([$campaignId,$s['id'],$s['email'],bin2hex(random_bytes(32)),bin2hex(random_bytes(32))]);
            $this->db->prepare("UPDATE newsletter_campaigns SET status='queued',queued_at=NOW() WHERE id=?")->execute([$campaignId]);
            $this->db->commit();return ['success'=>true,'message'=>'Đã xếp '.count($subs).' người nhận đã double opt-in vào hàng đợi.'];
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();return ['success'=>false,'message'=>$e->getMessage()];}
    }

    public function process(int $campaignId,int $limit=100): array {
        if(!MailService::isConfigured())return ['success'=>false,'message'=>'SMTP chưa được cấu hình; chưa gửi chiến dịch.'];
        $stmt=$this->db->prepare("SELECT r.*,c.subject,c.body_html,c.target_url,ns.unsubscribe_token FROM newsletter_campaign_recipients r
            JOIN newsletter_campaigns c ON c.id=r.campaign_id JOIN newsletter_subscriptions ns ON ns.id=r.subscription_id
            WHERE r.campaign_id=? AND r.status IN('pending','failed') AND ns.status='subscribed' LIMIT ?");
        $stmt->bindValue(1,$campaignId,PDO::PARAM_INT);$stmt->bindValue(2,max(1,min(500,$limit)),PDO::PARAM_INT);$stmt->execute();
        $sent=0;$failed=0;
        $this->db->prepare("UPDATE newsletter_campaigns SET status='sending' WHERE id=?")->execute([$campaignId]);
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r){
            $open=App::publicUrl('newsletter/open?token='.$r['open_token']);$click=App::publicUrl('newsletter/click?token='.$r['click_token']);$unsub=App::publicUrl('newsletter/unsubscribe?token='.$r['unsubscribe_token']);
            $html=$r['body_html'];
            if($r['target_url'])$html.='<p><a href="'.htmlspecialchars($click,ENT_QUOTES,'UTF-8').'">Xem nội dung</a></p>';
            $html.='<img src="'.htmlspecialchars($open,ENT_QUOTES,'UTF-8').'" width="1" height="1" alt=""><p style="font-size:12px"><a href="'.htmlspecialchars($unsub,ENT_QUOTES,'UTF-8').'">Hủy nhận tin</a></p>';
            try{MailService::sendHtml($r['email'],$r['subject'],$html);$this->db->prepare("UPDATE newsletter_campaign_recipients SET status='sent',sent_at=NOW(),last_error=NULL WHERE id=?")->execute([$r['id']]);$sent++;}
            catch(Throwable $e){$this->db->prepare("UPDATE newsletter_campaign_recipients SET status='failed',last_error=? WHERE id=?")->execute([substr($e->getMessage(),0,1000),$r['id']]);$failed++;}
        }
        $remaining=$this->db->prepare("SELECT COUNT(*) FROM newsletter_campaign_recipients WHERE campaign_id=? AND status<>'sent'");$remaining->execute([$campaignId]);
        if((int)$remaining->fetchColumn()===0)$this->db->prepare("UPDATE newsletter_campaigns SET status='sent',sent_at=NOW() WHERE id=?")->execute([$campaignId]);
        elseif($sent===0&&$failed>0)$this->db->prepare("UPDATE newsletter_campaigns SET status='failed' WHERE id=?")->execute([$campaignId]);
        return ['success'=>$failed===0,'message'=>"Đã gửi $sent email, lỗi $failed email."];
    }

    public function processQueued(int $campaignLimit = 10, int $recipientLimit = 100): array {
        $campaignLimit = max(1, min(50, $campaignLimit));
        $stmt = $this->db->prepare("SELECT id FROM newsletter_campaigns
            WHERE status IN ('queued','sending') ORDER BY queued_at ASC, id ASC LIMIT ?");
        $stmt->bindValue(1, $campaignLimit, PDO::PARAM_INT);
        $stmt->execute();

        $processed = 0;
        $failed = 0;
        $messages = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $campaignId) {
            $result = $this->process((int)$campaignId, $recipientLimit);
            $processed++;
            if (empty($result['success'])) {
                $failed++;
            }
            $messages[] = '#' . (int)$campaignId . ': ' . (string)($result['message'] ?? 'Không có kết quả.');
        }

        return [
            'success' => $failed === 0,
            'processed' => $processed,
            'failed' => $failed,
            'message' => $processed === 0
                ? 'Không có chiến dịch newsletter đang chờ.'
                : implode(' ', $messages),
        ];
    }

    public function markOpen(string $token): void {
        if(preg_match('/^[a-f0-9]{64}$/',$token))$this->db->prepare('UPDATE newsletter_campaign_recipients SET opened_at=COALESCE(opened_at,NOW()) WHERE open_token=?')->execute([$token]);
    }

    public function clickTarget(string $token): ?string {
        if(!preg_match('/^[a-f0-9]{64}$/',$token))return null;
        $stmt=$this->db->prepare("SELECT c.target_url FROM newsletter_campaign_recipients r JOIN newsletter_campaigns c ON c.id=r.campaign_id WHERE r.click_token=? LIMIT 1");$stmt->execute([$token]);$url=$stmt->fetchColumn();
        if(!$url)return null;$this->db->prepare('UPDATE newsletter_campaign_recipients SET clicked_at=COALESCE(clicked_at,NOW()),opened_at=COALESCE(opened_at,NOW()) WHERE click_token=?')->execute([$token]);
        return (string)$url;
    }
}
