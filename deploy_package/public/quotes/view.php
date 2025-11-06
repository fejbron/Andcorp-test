<?php
require_once '../bootstrap.php';
Auth::requireAuth();

$quoteRequestModel = new QuoteRequest();

$requestId = Security::sanitizeInt($_GET['id'] ?? 0);
if (!$requestId) {
    setErrors(['general' => 'Invalid quote request ID']);
    redirect(url('quotes.php'));
}

try {
    $request = $quoteRequestModel->findById($requestId);
    
    if (!$request || empty($request)) {
        setErrors(['general' => 'Quote request not found']);
        redirect(url('quotes.php'));
    }
    
    // Ensure the customer can only view their own quote requests
    $user = Auth::user();
    $customerModel = new Customer();
    $customer = $customerModel->findByUserId($user['id']);
    
    if (!$customer || $request['customer_id'] != $customer['id']) {
        setErrors(['general' => 'Quote request not found or access denied']);
        redirect(url('quotes.php'));
    }
    
    // Calculate totals if quote is ready
    $totalEstimate = 0;
    if ($request['quoted_price'] && $request['shipping_cost'] && $request['duty_estimate']) {
        $totalEstimate = floatval($request['quoted_price']) + floatval($request['shipping_cost']) + floatval($request['duty_estimate']);
    } elseif ($request['total_estimate']) {
        $totalEstimate = floatval($request['total_estimate']);
    }
    
} catch (Exception $e) {
    error_log("Error loading quote request: " . $e->getMessage());
    setErrors(['general' => 'An error occurred while loading the quote request. Please try again.']);
    redirect(url('quotes.php'));
}

$title = "Quote Request #" . ($request['request_number'] ?? 'N/A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - Andcorp Autos</title>
    <link rel="icon" type="image/png" href="<?php echo url('assets/images/favicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo url('assets/images/logo.png'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1"><i class="bi bi-file-earmark-text"></i> <?php echo htmlspecialchars($title); ?></h2>
                        <p class="text-muted mb-0">View your quote request details</p>
                    </div>
                    <a href="<?php echo url('quotes.php'); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to My Requests
                    </a>
                </div>

                <?php if (error('general')): ?>
                    <div class="alert alert-danger alert-dismissible fade show animate-in">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo error('general'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($successMsg = success()): ?>
                    <div class="alert alert-success alert-dismissible fade show animate-in">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo $successMsg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Status Badge -->
                <div class="card-modern mb-4 animate-in">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Request Status</h6>
                                <h4 class="mb-0">
                                    <span class="badge <?php 
                                        echo match($request['status'] ?? '') {
                                            'pending' => 'bg-warning',
                                            'reviewing' => 'bg-info',
                                            'quoted' => 'bg-success',
                                            'approved' => 'bg-primary',
                                            'rejected' => 'bg-danger',
                                            'converted' => 'bg-dark',
                                            default => 'bg-secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($request['status'] ?? 'Unknown'); ?>
                                    </span>
                                </h4>
                            </div>
                            <div class="text-end">
                                <p class="text-muted mb-1 small">Submitted</p>
                                <p class="mb-0"><strong><?php echo date('M d, Y', strtotime($request['created_at'] ?? 'now')); ?></strong></p>
                                <?php if ($request['quoted_at']): ?>
                                    <p class="text-muted mb-1 small mt-2">Quoted</p>
                                    <p class="mb-0"><strong><?php echo date('M d, Y', strtotime($request['quoted_at'])); ?></strong></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Vehicle Details -->
                    <div class="col-md-6 mb-4">
                        <div class="card-modern h-100 animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-car-front"></i> Vehicle Details</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        <?php if ($request['vehicle_type']): ?>
                                            <tr>
                                                <td class="text-muted" style="width: 40%;">Vehicle Type:</td>
                                                <td><strong><?php echo ucfirst($request['vehicle_type']); ?></strong></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if ($request['make']): ?>
                                            <tr>
                                                <td class="text-muted">Make:</td>
                                                <td><strong><?php echo $request['make']; ?></strong></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if ($request['model']): ?>
                                            <tr>
                                                <td class="text-muted">Model:</td>
                                                <td><strong><?php echo $request['model']; ?></strong></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if ($request['year']): ?>
                                            <tr>
                                                <td class="text-muted">Year:</td>
                                                <td><strong><?php echo $request['year']; ?></strong></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if ($request['trim']): ?>
                                            <tr>
                                                <td class="text-muted">Trim:</td>
                                                <td><?php echo $request['trim']; ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if ($request['vin']): ?>
                                            <tr>
                                                <td class="text-muted">VIN:</td>
                                                <td><code><?php echo $request['vin']; ?></code></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if ($request['lot_number']): ?>
                                            <tr>
                                                <td class="text-muted">Lot Number:</td>
                                                <td><code><?php echo $request['lot_number']; ?></code></td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if ($request['auction_link']): ?>
                                            <tr>
                                                <td class="text-muted">Auction Link:</td>
                                                <td>
                                                    <a href="<?php echo $request['auction_link']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-link-45deg"></i> View Auction
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php if ($request['preferred_color']): ?>
                                            <tr>
                                                <td class="text-muted">Preferred Color:</td>
                                                <td><?php echo $request['preferred_color']; ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Budget & Requirements -->
                    <div class="col-md-6 mb-4">
                        <div class="card-modern h-100 animate-in">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-wallet2"></i> Budget & Requirements</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($request['budget_min'] || $request['budget_max']): ?>
                                    <h6 class="text-muted mb-2">Budget Range</h6>
                                    <div class="d-flex justify-content-between mb-3 p-3 bg-light rounded">
                                        <div>
                                            <small class="text-muted d-block">Minimum</small>
                                            <h5 class="mb-0"><?php echo formatCurrency($request['budget_min']); ?></h5>
                                        </div>
                                        <div class="text-center align-self-center">
                                            <i class="bi bi-arrow-left-right text-muted"></i>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block">Maximum</small>
                                            <h5 class="mb-0"><?php echo formatCurrency($request['budget_max']); ?></h5>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($request['additional_requirements']): ?>
                                    <h6 class="text-muted mb-2">Additional Requirements</h6>
                                    <div class="p-3 bg-light rounded">
                                        <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($request['additional_requirements'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!$request['budget_min'] && !$request['budget_max'] && !$request['additional_requirements']): ?>
                                    <p class="text-muted mb-0 text-center py-3">No budget or requirements specified</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quote Details (if available) -->
                <?php if ($request['quoted_price']): ?>
                    <div class="card-modern mb-4 animate-in">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-check-circle"></i> Quote Ready!</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded">
                                        <small class="text-muted d-block mb-1">Vehicle Price</small>
                                        <h4 class="mb-0 text-primary"><?php echo formatCurrency($request['quoted_price']); ?></h4>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded">
                                        <small class="text-muted d-block mb-1">Shipping Cost</small>
                                        <h4 class="mb-0 text-info"><?php echo formatCurrency($request['shipping_cost']); ?></h4>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-light rounded">
                                        <small class="text-muted d-block mb-1">Duty Estimate</small>
                                        <h4 class="mb-0 text-warning"><?php echo formatCurrency($request['duty_estimate']); ?></h4>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-success text-white rounded">
                                        <small class="d-block mb-1">Total Estimate</small>
                                        <h4 class="mb-0"><?php echo formatCurrency($totalEstimate); ?></h4>
                                    </div>
                                </div>
                            </div>

                            <?php if ($request['admin_notes']): ?>
                                <div class="alert alert-info mb-0">
                                    <h6 class="mb-2"><i class="bi bi-info-circle"></i> Admin Notes:</h6>
                                    <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($request['status'] === 'quoted' || $request['status'] === 'approved'): ?>
                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Quote Ready!</strong> 
                                    <?php if ($request['status'] === 'quoted'): ?>
                                        Please review the quote above. If you're satisfied with the quote, please contact us to proceed with creating your order. Our team will assist you with the next steps.
                                    <?php elseif ($request['status'] === 'approved'): ?>
                                        This quote has been approved. Our team will create an order for you soon. You'll be notified once the order is ready.
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($request['order_id']): ?>
                                <div class="alert alert-success mt-3 mb-0">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <strong>Order Created!</strong> This quote has been converted to an order.
                                    <a href="<?php echo url('orders/view.php?id=' . $request['order_id']); ?>" class="btn btn-sm btn-success ms-2">
                                        <i class="bi bi-box-seam"></i> View Order
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card-modern mb-4 animate-in">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-hourglass-split" style="font-size: 3rem; color: var(--primary-color);"></i>
                            <h4 class="mt-3 mb-2">Quote In Progress</h4>
                            <p class="text-muted mb-0">Our team is reviewing your request and will provide a quote soon.</p>
                        </div>
                    </div>
                <?php endif; ?>


            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php clearErrors(); clearOld(); ?>

