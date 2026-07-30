<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PaceUp</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo" style="margin-bottom: 2rem;">
            <a href="/admin" style="color: white;">PaceUp</a>
        </div>
        <ul class="sidebar-nav">
            <li><a href="/admin" class="active"><i class="fas fa-tachometer-alt" style="width: 25px;"></i> Dashboard</a></li>
            <li><a href="/admin/products"><i class="fas fa-box" style="width: 25px;"></i> Products</a></li>
            <li><a href="/admin/orders"><i class="fas fa-shopping-cart" style="width: 25px;"></i> Orders</a></li>
            <li><a href="/admin/users"><i class="fas fa-users" style="width: 25px;"></i> Users</a></li>
            <li><a href="/admin/content"><i class="fas fa-file-alt" style="width: 25px;"></i> Content</a></li>
            <li style="margin-top: 2rem;"><a href="/logout"><i class="fas fa-sign-out-alt" style="width: 25px;"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="admin-header">
            <h1 style="font-size: 1.8rem; font-weight: 700; text-transform: uppercase;">Dashboard</h1>
            <div class="user-info">
                <span>Welcome, Admin</span>
            </div>
        </header>

        <section style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.2rem; margin-bottom: 1rem; text-transform: uppercase;">Recent Products</h2>
            <div style="margin-bottom: 1rem;">
                <button class="btn btn-dark" style="padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.9rem;"><i class="fas fa-plus"></i> Add New Product</button>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td><img src="/assets/images/AIR+ZOOM+PEGASUS+42+WIDE.avif" alt="Shoe" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;"></td>
                        <td>Air Zoom Pegasus 42</td>
                        <td>Men's Running</td>
                        <td>$120.00</td>
                        <td>
                            <button class="btn" style="padding: 0.3rem 0.5rem; background: #e0e0e0; border-radius: 4px; margin-right: 0.5rem;"><i class="fas fa-edit"></i> Edit</button>
                            <button class="btn" style="padding: 0.3rem 0.5rem; background: #ff4d4d; color: white; border-radius: 4px;"><i class="fas fa-trash"></i> Delete</button>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td><img src="/assets/images/NIKE+SB+DUNK+LOW+PRO.avif" alt="Shoe" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;"></td>
                        <td>SB Dunk Low Pro</td>
                        <td>Skateboarding</td>
                        <td>$110.00</td>
                        <td>
                            <button class="btn" style="padding: 0.3rem 0.5rem; background: #e0e0e0; border-radius: 4px; margin-right: 0.5rem;"><i class="fas fa-edit"></i> Edit</button>
                            <button class="btn" style="padding: 0.3rem 0.5rem; background: #ff4d4d; color: white; border-radius: 4px;"><i class="fas fa-trash"></i> Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>
</div>

</body>
</html>