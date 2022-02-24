<?php
include('./init_01.php');
include('./process_main.php');
Page_SessionStats(0);

$_user_acct = getLoggedID();

$account_eng = new uaccount($_user_acct);
$transc_details = $account_eng->get_report();
$acc_bal_recs = $account_eng->get_balance('');
$acct_details = $account_eng->get_acct_info();
$_acct_email = (isset($acct_details['email'])) ? $acct_details['email'] : '';
$_acct_fullname  = (isset($acct_details['fullname'])) ? strtoupper($acct_details['fullname']) : 'UNKNOWN';
echo "<pre>";
//print_r($transc_details);
//print_r($acc_bal_recs);
print_r($acct_details);
echo "</pre>";
?>
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Home || SIMBA</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,400i,500,700" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,500,600,700,800i" rel="stylesheet">

    <!-- favicon icon -->
    <link rel="shortcut icon" type="images/png" href="images/favicon.ico">

    <!-- all css here -->
    <link rel="stylesheet" href="style.css">
    <!-- modernizr css -->
    <script src="js/vendor/modernizr-2.8.3.min.js"></script>
</head>

<body>
    <!--[if lt IE 8]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->
    <!-- Add your site or application content here -->
    <!--Start-Preloader-area-->
    <div class="preloader">
        <div class="loading-center">
            <div class="loading-center-absolute">
                <div class="object object_one"></div>
                <div class="object object_two"></div>
                <div class="object object_three"></div>
            </div>
        </div>
    </div>
    <!--end-Preloader-area-->
    <!--header-area-start-->
    <!--Start-main-wrapper-->
    <div class="page-1 shopping-cart">
        <!--Start-Header-area-->
        <div id="header"></div>
        <!--End-Header-area-->
        <!--start-single-heading-banner-->
        <!--end-single-heading-banner-->
        <!--start-single-heading-->
        <!--end-single-heading-->
        <!-- cart-main-area start-->
        <div class="cart-main-area">
            <div class="container">
                <div class="row">

                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <div class="your-order">
                            <h3>Welcome, <?= $_acct_fullname ?> </h3>
                            <div class="your-order-table table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="product-name">Account</th>
                                            <th class="product-total">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="cart_item">
                                            <td class="product-name">
                                                Account ID
                                            </td>
                                            <td class="product-total">
                                                <strong class="product-quantity"><?= $_user_acct ?></strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="product-name">
                                                email
                                            </td>
                                            <td class="product-total">
                                                <?= $_acct_email ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                        <div class="your-order">
                            <h3>Account Details</h3>
                            <div class="your-order-table table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th class="product-name">Account</th>
                                            <th class="product-total">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (!empty($acc_bal_recs)) {
                                            foreach ($acc_bal_recs as $acctbal) {
                                                echo "
                                                    <tr class='cart_item'>
                                                        <td class='product-name'>
                                                            {$acctbal['currency_desc']}
                                                        </td>
                                                        <td class='product-total'>
                                                            <span class='amount'>
                                                            <font style='color:#ffbb00; font-size:20px; font-weight:700'>
                                                                {$acctbal['bal_display']}
                                                            </font>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                ";
                                            }
                                        }
                                        ?>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <form action="#">
                            <div class="wc-proceed-to-checkout">
                                <a href="./transact.html">NEW TRANSACTION</a>
                            </div>
                            <h2><b>Transactions</b></h2>

                            <div class="table-content table-responsive">

                                <table>
                                    <thead>
                                        <tr>
                                            <th class="product-name">ID</th>
                                            <th class="product-name">From</th>
                                            <th class="product-name">To</th>
                                            <th class="product-price">Value</th>
                                            <th class="product-subtotal">Currency</th>
                                            <th class="product-subtotal">Status</th>
                                            <th class="product-name">Created At</th>
                                            <th class="product-name">Updated At</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cart_content">
                                        <?php
                                        if (!empty($transc_details)) {
                                            foreach ($transc_details as $trn_det) {
                                                echo "<tr>
                                                        <td>{$trn_det['transc_id']}</td>
                                                        <td class='product-name'>{$trn_det['from_name']}</td>
                                                        <td class='product-name'>{$trn_det['to_name']}</td>
                                                        <td class='product-price'>{$trn_det['curval_display']}</td>
                                                        <td class='product-subtotal'>{$trn_det['targ_currency']}</td>
                                                        <td class='product-name'>{$trn_det['trnstatus_desc']}</td>
                                                        <td class='product-name'>{$trn_det['createdat_desc']}</td>
                                                        <td class='product-name'>{$trn_det['updatedat_desc']}</td>
                                                      </tr>
                                                ";
                                            }
                                        }
                                        ?>
                                        <!--tr>
                                            <td class="product-thumbnail"><a href="#"><img src="images/cart/3.jpg" alt="" /></a></td>
                                            <td class="product-name"><a href="#">Sample Product</a></td>
                                            <td class="product-price">
                                                <font style="color: green;">£125.00</font>
                                            </td>
                                            <td class="product-quantity"><input type="number" value="1" /></td>
                                            <td class="product-subtotal">£125.00</td>
                                            <td class="product-remove"><a href="#"><i class="fa fa-times"></i></a></td>
                                        </tr>
                                        <tr>
                                            <td class="product-thumbnail"><a href="#"><img src="images/cart/4.jpg" alt="" /></a></td>
                                            <td class="product-name"><a href="#">Sample Product</a></td>
                                            <td class="product-price"><span class="amount">£90.00</span></td>
                                            <td class="product-quantity"><input type="number" value="1" /></td>
                                            <td class="product-subtotal">£90.00</td>
                                            <td class="product-remove"><a href="#"><i class="fa fa-times"></i></a></td>
                                        </tr-->
                                    </tbody>
                                </table>
                            </div>
                            <div class="row">
                                <div class="col-lg-8 col-md-8 col-sm-7 col-xs-12">

                                    <!-- <div class="buttons-cart">
                                            <input class="banner-r-b" type="submit" value="Update Cart" />
                                            <a href="#">Continue Shopping</a>
                                        </div>
                                        <div class="coupon">
                                            <h3>Coupon</h3>
                                            <p>Enter your coupon code if you have one.</p>
                                            <input type="text" placeholder="Coupon code" />
                                            <input type="submit" value="Apply Coupon" />
                                        </div>-->
                                </div>
                                <!--<div class="col-lg-4 col-md-4 col-sm-5 col-xs-12">
                                        <div class="cart_totals">
                                            <h2>Cart Totals</h2>
                                            <table>
                                                <tbody>
                                                    <tr class="cart-subtotal">
                                                        <th>Total Amount to be Paid</th>
                                                        <td><span class="amount">£215.00</span></td>
                                                    </tr>
                                                    <tr class="shipping">
                                                        <th>Shipping</th>
                                                        <td>
                                                            <ul id="shipping_method">
                                                                <li>
                                                                    <input type="radio" />
                                                                    <label>
                                                                        Flat Rate: <span class="amount">£7.00</span>
                                                                    </label>
                                                                </li>
                                                                <li>
                                                                    <input type="radio" />
                                                                    <label>
                                                                        Free Shipping
                                                                    </label>
                                                                </li>
                                                                <li></li>
                                                            </ul>
                                                            <p><a class="shipping-calculator-button" href="#">Calculate Shipping</a></p>
                                                        </td>
                                                    </tr>
                                                    <tr class="order-total">
                                                        <th>Total</th>
                                                        <td>
                                                            <strong><span class="amount">£215.00</span></strong>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <div class="wc-proceed-to-checkout">
                                                <a href="#">Proceed to Checkout</a>
                                            </div>
                                        </div>
                                    </div>-->
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- cart-main-area end -->
        <!--Start-brand-area
            <div class="brands-area brand-cart">
                <div class="container">-->
        <!--barand-heading
                    <div class="brand-heading text-center">
                        <h2>Popular brands</h2>
                    </div>-->
        <!--brand-heading-end
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <div class="brands-carousel">-->
        <!--start-single-brand
                                <div class="single-brand">
                                    <a href="#"><img src="images/brands/1.png" alt=""></a>
                                </div>-->
        <!--end-single-brand-->
        <!--start-single-brand
                                <div class="single-brand">
                                    <a href="#"><img src="images/brands/2.png" alt=""></a>
                                </div>-->
        <!--end-single-brand-->
        <!--start-single-brand
                                <div class="single-brand">
                                    <a href="#"><img src="images/brands/3.png" alt=""></a>
                                </div>-->
        <!--end-single-brand-->
        <!--start-single-brand
                                <div class="single-brand">
                                    <a href="#"><img src="images/brands/4.png" alt=""></a>
                                </div>-->
        <!--end-single-brand-->
        <!--start-single-brand
                                <div class="single-brand">
                                    <a href="#"><img src="images/brands/1.png" alt=""></a>
                                </div>-->
        <!--end-single-brand-->
        <!--start-single-brand
                                <div class="single-brand">
                                    <a href="#"><img src="images/brands/2.png" alt=""></a>
                                </div>-->
        <!--end-single-brand-->
        <!--start-single-brand
                                <div class="single-brand">
                                    <a href="#"><img src="images/brands/3.png" alt=""></a>
                                </div>-->
        <!--end-single-brand
                            </div>
                        </div>
                    </div>
                </div>
            </div>-->
        <!--End-brand-area-->
        <!--Start-footer-wrap-->
        <div id="footer"></div>
        <!--End-footer-wrap-->

    </div>
    <!--End-main-wrapper-->

    <!-- all js here -->
    <!-- jquery latest version -->
    <script src="js/vendor/jquery-1.12.0.min.js"></script>
    <!--cookies js-->
    <script src="js/custom/cookies.js"></script>
    <!-- PageScript00 js -->
    <script src="js/custom/PageScript00.js"></script>
    <!-- PageScript01 js -->
    <script src="js/custom/PageScript01.js"></script>
    <!-- pageShopC js -->
    <script src="js/custom/pageShopC.js"></script>
    <!-- bootstrap js -->
    <script src="js/bootstrap.min.js"></script>
    <!-- owl.carousel js -->
    <script src="js/owl.carousel.min.js"></script>
    <!-- meanmenu.js -->
    <script>
        var angle = Math.floor(Math.random() * 100);
        var SOURCE = "js/jquery.meanmenu.js?dev=" + angle;
        document.write('<script src=' + SOURCE + '\> <\/script>');
    </script>
    <!-- nivo.slider.js -->
    <script src="lib/js/jquery.nivo.slider.js" type="text/javascript"></script>
    <script src="lib/home.js" type="text/javascript"></script>
    <!-- jquery-ui js -->
    <script src="js/jquery-ui.min.js"></script>
    <!-- scrollUp.min.js -->
    <script src="js/jquery.scrollUp.min.js"></script>
    <!-- jquery.parallax.js -->
    <script src="js/jquery.parallax.js"></script>
    <!-- sticky.js -->
    <script src="js/jquery.sticky.js"></script>
    <!-- jquery.simpleGallery.min.js -->
    <script src="js/jquery.simpleGallery.min.js"></script>
    <script src="js/jquery.simpleLens.min.js"></script>
    <!-- countdown.min.js -->
    <script src="js/jquery.countdown.min.js"></script>
    <!-- isotope.pkgd.min -->
    <script src="js/isotope.pkgd.min.js"></script>
    <!-- wow js -->
    <script src="js/wow.min.js"></script>
    <!-- plugins js -->
    <script src="js/plugins.js"></script>
    <!-- main js -->
    <script src="js/main.js"></script>
</body>

</html>