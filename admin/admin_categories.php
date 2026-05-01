<?php
require_once '../config.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

require_admin();

$db = Database::getInstance()->getConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        
        $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        if ($stmt->execute([$name, $description])) {
            $message = '<div class="alert alert-success">Category added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Failed to add category.</div>';
        }
    }
    
    if (isset($_POST['edit_category'])) {
        $category_id = (int)$_POST['category_id'];
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        
        $stmt = $db->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        if ($stmt->execute([$name, $description, $category_id])) {
            $message = '<div class="alert alert-success">Category updated successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Failed to update category.</div>';
        }
    }
    
    if (isset($_POST['delete_category'])) {
        $category_id = (int)$_POST['category_id'];
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM listings WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $message = '<div class="alert alert-danger">Cannot delete category with existing listings!</div>';
        } else {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$category_id])) {
                $message = '<div class="alert alert-success">Category deleted successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to delete category.</div>';
            }
        }
    }
    
    if (isset($_POST['add_subcategory'])) {
        $parent_id = (int)$_POST['parent_id'];
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        
        $stmt = $db->prepare("INSERT INTO categories (name, description, parent_id) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $description, $parent_id])) {
            $message = '<div class="alert alert-success">Subcategory added successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Failed to add subcategory.</div>';
        }
    }
    
    if (isset($_POST['delete_subcategory'])) {
        $subcategory_id = (int)$_POST['subcategory_id'];
        
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM listings WHERE category_id = ?");
        $stmt->execute([$subcategory_id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $message = '<div class="alert alert-danger">Cannot delete subcategory with existing listings!</div>';
        } else {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$subcategory_id])) {
                $message = '<div class="alert alert-success">Subcategory deleted successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to delete subcategory.</div>';
            }
        }
    }
}

$stmt = $db->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM listings WHERE category_id = c.id) as listing_count
    FROM categories c
    WHERE c.parent_id IS NULL
    ORDER BY c.name
");
$categories = $stmt->fetchAll();

foreach($categories as &$category) {
    $stmt = $db->prepare("
        SELECT c.*,
               (SELECT COUNT(*) FROM listings WHERE category_id = c.id) as listing_count
        FROM categories c
        WHERE c.parent_id = ?
        ORDER BY c.name
    ");
    $stmt->execute([$category['id']]);
    $category['subcategories'] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin_categories.css">
</head>
<body>
    <div class="admin-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> Admin Panel</h3>
                <small>Thrift Store Management</small>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin_users.php">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <a href="admin_listings.php">
                    <i class="fas fa-box"></i>
                    <span>Manage Listings</span>
                </a>
                <a href="admin_categories.php" class="active">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
                <a href="admin_messages.php">
                    <i class="fas fa-comments"></i>
                    <span>Messages</span>
                </a>
                <a href="admin_contact_messages.php">
                    <i class="fas fa-envelope"></i>
                    <span>Contact Messages</span>
                </a>
                <a href="admin_reviews.php">
                    <i class="fas fa-star"></i>
                    <span>Reviews</span>
                </a>
                <hr style="border-color: rgba(255,255,255,0.1); margin: 20px 0;">
                <a href="../index.php">
                    <i class="fas fa-globe"></i>
                    <span>View Site</span>
                </a>
                <a href="../assets/auth/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Manage Categories</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </div>

            <?php echo $message; ?>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-tags"></i> All Categories
                </div>
                <div class="card-body">
                    <?php if(empty($categories)): ?>
                        <p class="text-muted text-center">No categories found</p>
                    <?php else: ?>
                        <?php foreach($categories as $category): ?>
                            <div class="category-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                                        <p class="text-muted mb-0"><?php echo htmlspecialchars($category['description']); ?></p>
                                        <small class="text-muted"><?php echo $category['listing_count']; ?> listings</small>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-primary" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($category['description'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-success" onclick="addSubcategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-plus"></i> Add Sub
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <?php if(!empty($category['subcategories'])): ?>
                                    <div class="ms-4">
                                        <h6 class="text-muted mb-2">Subcategories:</h6>
                                        <?php foreach($category['subcategories'] as $sub): ?>
                                            <div class="subcategory-item">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($sub['name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($sub['description']); ?> (<?php echo $sub['listing_count']; ?> listings)</small>
                                                </div>
                                                <div>
                                                    <button class="btn btn-sm btn-primary" onclick="editCategory(<?php echo $sub['id']; ?>, '<?php echo htmlspecialchars($sub['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($sub['description'], ENT_QUOTES); ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this subcategory?');">
                                                        <input type="hidden" name="subcategory_id" value="<?php echo $sub['id']; ?>">
                                                        <button type="submit" name="delete_subcategory" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" class="form-control" name="name" id="edit_category_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_category_description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_category" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addSubcategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Subcategory to <span id="parent_category_name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="parent_id" id="parent_category_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Subcategory Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_subcategory" class="btn btn-primary">Add Subcategory</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="admin-mobile.js"></script>
    <script>
        function editCategory(id, name, description) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            document.getElementById('edit_category_description').value = description;
            new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
        }

        function addSubcategory(parentId, parentName) {
            document.getElementById('parent_category_id').value = parentId;
            document.getElementById('parent_category_name').textContent = parentName;
            new bootstrap.Modal(document.getElementById('addSubcategoryModal')).show();
        }
    </script>
</body>
</html>