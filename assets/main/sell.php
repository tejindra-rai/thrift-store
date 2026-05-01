<?php
require_once '../../config.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

if (!is_logged_in()) {
    header("Location: " . SITE_URL . "/assets/auth/login.php");
    exit();
}

$error = '';
$success = '';
$db = Database::getInstance()->getConnection();

$stmt = $db->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
$main_categories = $stmt->fetchAll();

$subcategories_by_parent = [];
foreach($main_categories as $main_cat) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY name");
    $stmt->execute([$main_cat['id']]);
    $subcategories_by_parent[$main_cat['id']] = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $price = (float)$_POST['price'];
    $category_id = (int)$_POST['category_id'];
    $size = sanitize_input($_POST['size']);
    $condition = sanitize_input($_POST['condition']);
    $user_id = get_current_user_id();
    
    if (empty($title) || empty($description) || $price <= 0 || empty($condition)) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO listings (user_id, title, description, price, category_id, size, `condition`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $title, $description, $price, $category_id, $size, $condition]);
            $listing_id = $db->lastInsertId();
            
            if (!empty($_FILES['images']['name'][0])) {
                $upload_errors = [];
                $first_image = true;
                
                foreach ($_FILES['images']['name'] as $key => $name) {
                    if ($_FILES['images']['error'][$key] == 0) {
                        $file = [
                            'name' => $_FILES['images']['name'][$key],
                            'type' => $_FILES['images']['type'][$key],
                            'tmp_name' => $_FILES['images']['tmp_name'][$key],
                            'error' => $_FILES['images']['error'][$key],
                            'size' => $_FILES['images']['size'][$key]
                        ];
                        
                        $filename = upload_image($file, 'listing');
                        if ($filename) {
                            $stmt = $db->prepare("INSERT INTO listing_images (listing_id, image_path, is_primary) VALUES (?, ?, ?)");
                            $stmt->execute([$listing_id, $filename, $first_image ? 1 : 0]);
                            $first_image = false;
                        } else {
                            $upload_errors[] = "Failed to upload: " . $name;
                        }
                    }
                }
                
                if (!empty($upload_errors)) {
                    $error = implode(", ", $upload_errors);
                }
            }
            
            $db->commit();
            $success = 'Listing created successfully!';
            header("refresh:2;url=listing_detail.php?id=" . $listing_id);
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Failed to create listing. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Item - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="main_css/sell.css">
</head>
<body>
    <?php include '../../assets/includes/header.php'; ?>

    <div class="container">
        <div class="sell-container">
            <h1 class="page-title"><i class="fas fa-plus-circle"></i> Create New Listing</h1>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <label class="form-label">Title <span class="required">*</span></label>
                        <input type="text" class="form-control" name="title" required placeholder="e.g., Vintage Denim Jacket">
                    </div>
                    
                    <div class="col-md-12 mb-4">
                        <label class="form-label">Description <span class="required">*</span></label>
                        <textarea class="form-control" name="description" rows="5" required placeholder="Describe your item in detail..."></textarea>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Price (Rs) <span class="required">*</span></label>
                        <input type="number" class="form-control" name="price" step="0.01" min="0" required placeholder="0.00">
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Main Category <span class="required">*</span></label>
                        <select class="form-select" id="mainCategory" required onchange="updateSubcategories()">
                            <option value="">Select Main Category</option>
                            <?php foreach($main_categories as $main_cat): ?>
                                <option value="<?php echo $main_cat['id']; ?>"><?php echo htmlspecialchars($main_cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Subcategory <span class="required">*</span></label>
                        <select class="form-select" name="category_id" id="subcategory" required disabled>
                            <option value="">Select Main Category First</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Size</label>
                        <select class="form-select" name="size">
                            <option value="">Select Size</option>
                            <option value="XS">XS</option>
                            <option value="S">S</option>
                            <option value="M">M</option>
                            <option value="L">L</option>
                            <option value="XL">XL</option>
                            <option value="XXL">XXL</option>
                            <option value="One Size">One Size</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Condition <span class="required">*</span></label>
                        <select class="form-select" name="condition" required>
                            <option value="">Select Condition</option>
                            <option value="Like New">Like New</option>
                            <option value="Excellent">Excellent</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Vintage">Vintage</option>
                        </select>
                    </div>
                    
                    <div class="col-md-12 mb-4">
                        <label class="form-label">Images (Max 5 images)</label>
                        <div class="image-upload-area" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p class="mb-0">Click to upload images</p>
                            <small class="text-muted">Supports: JPG, PNG, GIF (Max 5MB each)</small>
                        </div>
                        <input type="file" id="fileInput" name="images[]" multiple accept="image/*" style="display:none" onchange="previewImages(event)">
                        <div id="imagePreview" class="image-preview"></div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-check-circle"></i> Create Listing
                </button>
            </form>
        </div>
    </div>

    <?php include '../../assets/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const subcategoriesData = <?php echo json_encode($subcategories_by_parent); ?>;
        
        function updateSubcategories() {
            const mainCategoryId = document.getElementById('mainCategory').value;
            const subcategorySelect = document.getElementById('subcategory');
            
            subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            
            if (mainCategoryId && subcategoriesData[mainCategoryId]) {
                subcategorySelect.disabled = false;
                subcategoriesData[mainCategoryId].forEach(subcat => {
                    const option = document.createElement('option');
                    option.value = subcat.id;
                    option.textContent = subcat.name;
                    subcategorySelect.appendChild(option);
                });
            } else {
                subcategorySelect.disabled = true;
                subcategorySelect.innerHTML = '<option value="">Select Main Category First</option>';
            }
        }
        
        function previewImages(event) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            const files = event.target.files;
            
            if (files.length > 5) {
                alert('Maximum 5 images allowed');
                event.target.value = '';
                return;
            }
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>