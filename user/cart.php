<?php
include('../connection.php');
session_start();
$checkoutMessage = '';
if(empty($_SESSION['userID'])){
    echo "<script>window.location.href='../login.php';</script>";
}else{
    $userID = $_SESSION['userID'];
    $cartName = $userID."cart";
    $userEmail = $_SESSION['userEmail'];
    $userVoucher = $userID."vouchers";
    // echo $userVoucher;

    $sql = "SELECT * FROM userdetails where email = '$userEmail'";
    $result = mysqli_query($connection,$sql);
    $row = mysqli_fetch_array($result);
    if($row['email']==$userEmail){
        $name = $row['fName']." ".$row['lName'];
        $address = $row['address'];
        $contact = $row['contactNo']; 
    }

    if($_SERVER ["REQUEST_METHOD"] == "POST"){
        $semitotalprice = $_POST['totalPrice'];
        
        $semitotalprice = $semitotalprice + 50;
        
        $currentDate = date("Y-m-d");
        $paymentMethod = $_POST['paymentMethod'];
        $usedVoucher = $_POST['voucher'];
        
        $salesID = "";

        $usedVoucherSQL = "SELECT * from $userVoucher where voucherID = '$usedVoucher'";
        $usedVoucherResult = mysqli_query($connection,$usedVoucherSQL);
        $usedVoucherRow = mysqli_fetch_array($usedVoucherResult);
        if(!empty($usedVoucherRow)){
            $voucherDiscountPrice = $usedVoucherRow['voucherDiscount'];
            $totalprice = $semitotalprice - $voucherDiscountPrice;
            $UPDATEuservoucher = mysqli_query($connection, "UPDATE $userVoucher set status='Used' where voucherID = '$usedVoucher'");

        }else{
            $totalprice = $semitotalprice;
        }

        //input to sales table
        $salesSQL = mysqli_query($connection, "INSERT INTO sales values (null, '$totalprice', '$currentDate', '$userID', '$paymentMethod', 'Undelivered')");

        //select salesID from sales table
        $selectSales = "SELECT salesID from sales where salesDate = '$currentDate' and invoice = '$userID' and amount = '$totalprice'";
        $salesresult = mysqli_query($connection,$selectSales);
        $salesrow = mysqli_fetch_array($salesresult);
        if(!empty($salesrow['salesID'])){
            $salesID = $salesrow['salesID'];
        }else{
            echo "<script> alert('salesID is Empty')</script>";
        }
    
        $userPurchases = $userID."purchases";
        //insert delivery charge in purchases
        $deliveryCharge = mysqli_query($connection, "INSERT INTO ".$userPurchases." VALUES (0, 1, '$currentDate', '$salesID', '$paymentMethod', 'Undelivered')");
    
        $userCart = $userID."cart";
        //transfer from cart to purchases
        $cartSQL = "SELECT * FROM ".$userID."cart WHERE amount <= (SELECT productStock from products where products.productID = ".$userID."cart.productID) and amount > 0";
        $cartresult = mysqli_query($connection,$cartSQL);
        if(mysqli_num_rows($cartresult) > 0){
            while($cartrow = mysqli_fetch_array($cartresult)){

                $cartProdID = $cartrow['productID'];
                $cartProdAmount = $cartrow['amount'];
                $cartItems = mysqli_query($connection, "INSERT INTO ".$userPurchases." VALUES ('$cartProdID', '$cartProdAmount', '$currentDate', '$salesID', '$paymentMethod', 'Undelivered')");
                
                //check for number of purchases
                $canAvailVoucherSQL = "SELECT  COUNT(*) AS totalPurchases FROM ".$userID."purchases";
                $voucherResult = mysqli_query($connection,$canAvailVoucherSQL);
                $voucherRow = mysqli_fetch_array($voucherResult);
                if(!empty($voucherRow)){
                    $totalPurchases = $voucherRow['totalPurchases'];
                    //check if can avail for voucher
                    if($totalPurchases%10 == 0){
                        //check for available voucher

                        $availVoucherSQL = "SELECT * FROM vouchers WHERE voucherAmount > 0 LIMIT 1";
                        $allVoucherResult = mysqli_query($connection,$availVoucherSQL);

                        if(mysqli_num_rows($allVoucherResult) > 0){
                            while($allVoucherRow = mysqli_fetch_array($allVoucherResult)){
                                $voucherCode = $allVoucherRow['voucherID'];
                                $voucherName = addslashes($allVoucherRow['voucherName']);
                                $voucherDiscount = $allVoucherRow['voucherDiscount'];
                                echo $voucherCode;
                                //insert into uservouchers
                                $insertUserVouchers = mysqli_query($connection,"INSERT INTO $userVoucher VALUES ('$voucherCode','$voucherName', '$voucherDiscount', 'Unused')");

                                //update vouchers
                                $updateVouchers = mysqli_query($connection,"UPDATE vouchers SET voucherAmount = voucherAmount - 1 WHERE voucherID = '$voucherCode'");
                                
                                echo "<script> alert('CONGRATULATIONS! YOU WON A NEW VOUCHER');</script>";
                            }
                        }
                    }         
                }


                //update stock
                $updateStock = mysqli_query($connection, "UPDATE products SET productStock=productStock-'$cartProdAmount' where productID = '$cartProdID'");
                
                $deleteCart = mysqli_query($connection, "DELETE FROM ".$userCart." where productID=".$cartProdID."");
            }
        }
        $selectSales = "SELECT * from ".$userID."cart";
        $salesresult = mysqli_query($connection,$selectSales);
        $salesrow = mysqli_fetch_array($salesresult);
        if(!empty($salesrow)){
            $checkoutMessage = 'Some Items were not purchased due to stock issues';
            // echo "<script> alert('$checkoutMessage'); </script>";
        }else{
            $checkoutMessage = 'All Orders is being processed';
            // echo "<script> alert('$checkoutMessage'); </script>";
        }

    //delete cart items

    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <?php include('css.php');?>
    <title>OKAERI | Cart</title>
    <style>
        #checkout{
            position:fixed;
            bottom: 0;
            right:0;
        }
        @media only screen and (max-width : 768px) {
            #checkout{
                position: relative !important;
            }
        }
    </style>
</head>
<body>
    <header>
        <?php include('nav.php');?>
    </header>
    <!-- <p><?php echo $checkoutMessage; ?></p> -->
    <section id="tablecart" class="table-responsive">
        <table id="mytable" class="table table-bordered table-striped text-center mt-5">
            <thead class="thead-dark">
                <tr>
                    <th style="display:none;">Product ID</th>
                    <th>Cover</th>
                    <th>Title</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th></th>
                </tr>
            </thead>
            <?php
                $totalPrice = 0;
                $cartSql = "SELECT * FROM `$cartName` where amount > 0";
                $cartQuery = mysqli_query($connection,$cartSql);
                if($cartQuery->num_rows > 0 ){
                    while($row = $cartQuery->fetch_assoc()){
                        $productQuantity = $row['amount'];
                        $productID = $row['productID'];
                        $productDetails = "SELECT * FROM products WHERE productID = '$productID'";
                        $productDetailsQuery = mysqli_query($connection,$productDetails);
                        $productRow = mysqli_fetch_array($productDetailsQuery);
                        if($productRow['productID'] == $productID){
                            $productTitle = $productRow['productTitle'];
                            $productImage = $productRow['productImage'];
                            $productPrice = $productRow['productPrice'];
                            $productTPrice = $productQuantity * $productPrice;
                            $totalPrice = $totalPrice + $productTPrice; 
                                // echo $productTitle." ".$productImage." ".$productPrice;
                            ?>
                            <tr>
                                <td style="display:none;">
                                    <?php echo $productID;?>
                                </td>
                                <td>
                                    <img src="../<?php echo $productImage;?>" alt="">
                                </td>
                                <td>
                                    <?php echo $productTitle; ?>
                                </td>
                                <td>
                                    <?php echo $productQuantity; ?>
                                </td>
                                <td>
                                    <?php echo $productTPrice; ?>
                                </td>
                                <td>
                                    <button class="btn btn-primary" onclick="popup_three()"><i class="fa fa-edit"></i></button>
                                    <button onclick="popup()" class="btn btn-danger"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php  
                        }
                    }
                }
            ?>
            
        </table>
    </section>
    <section id="checkout" class="col-xl-4 position-fixed">
        <div class="jumbotron border border-dark py-3">
            <h5>Total Amount: <span>&#8369;</span><?php echo $totalPrice;?> </h5>
            <hr class="my-4 bg-dark">
            <!-- <div class= "mb-2 text-center">
                <button class="btn btn-primary" onclick="popup()"><i class= "fa fa-credit-card"></i> Checkout as Debit</button>
            </div> -->
            <div class= "mb-2 text-center">
                <button class="btn btn-success" onclick="popup_two()"><i class= "fa fa-truck"></i> Checkout as COD</button>
            </div>
        </div>
    </section>

    <!-- DO NOT CHANGE THIS SECTION -->
    <!-- DO NOT CHANGE -->
    <head>
        <style>
            .pop{
                position:fixed;
                width: 100%;
                height: 100vh;
                top: 8%;
            }
            .popup{
                display:none;
            }
            #modal img{
                width:100px;
                height:150px;
            }
            .cl{
                height: 25px;
                width: 20px;
                border-radius: 50%;
                cursor: pointer;
            }
        </style>
    </head>
    <!-- DELETE FROM CART UI -->
    <div id="delete" class="popup">
        <div class="row">
            <div class="col-xl-3 col-lg-4 col-md-4 col-sm-7 m-auto">
                <div class="jumbotron text-center">
                    <form action="deletecart.php" enctype="multipart/form-data" method="post">
                        <div>
                            <p class="lead">Are you sure you want to delete?</p>
                            <input type="text" id="pid" style="display:none;" name="pid">
                            <input type="hidden" style="background:transparent; border:none;" disabled class="w-100 text-dark" id="title_d" name="title_d">
                        </div>
                        <hr class="my-4">
                        <div>
                            <button type="Submit" class="btn btn-primary m-1">Yes</button>
                        </div>
                    </form>
                    <button class="btn btn-danger m-1" onclick ="popup()">No</button>
                </div>
            </div>
        </div>
    </div>
   
    <!-- UPDATE FROM CART UI -->
    <div id="edit" class="popup">
        <div class="jumbotron col-xl-3 col-lg-3 col-md-4 col-sm-7 border border-dark d-block m-auto text-center">
            <div class="cl row justify-content-center bg-warning text-dark text-center" onclick="popup_three()">x</div>
            <form action="updatecart.php" enctype="multipart/form-data" method="post">
                <p class="lead">Edit Quantity</p>
                <input type="text" id="pid_update" style="display:none;" name="pid_update">
                <input type="number" min="1" class="form-control" id="quantity_u" name="quantity_u">
                <div class="row justify-content-center">
                    <button type="Submit" class="btn btn-primary m-1">Update</button> 
                </div>
            </form>
            </div>
        </div>

    </div>
    <div id='v_alert' class='popup'>
        <p class='lead'>You Have claimed new Voucher!".$voucherName." with a Discount of ".$voucherDiscount."</p>
        <button class='btn btn-warning' onclick='popup_alert()'>Claim</button>
    </div>
    <!-- CHECKOUT COD -->
    <div id="modal2" class="popup">
        <div class="row w-100 justify-content-center m-0">
            <div class="jumbotron bg-dark text-white">
                <div class="cl row justify-content-center bg-warning text-dark text-center" onclick="popup_two()">x</div>
                <h4>Please Confirm the information below</h4>
                <p for="">Addressed to: <?php echo $name; ?></p>
                <p for="">Delivery Address: <?php echo $address; ?></p>
                <p for="">Delivered by: Ninja Van</p>
                <div class="container">
                    <div class="row align-content-center">
                        <label for="deliveramount">Delivery Amount:</label>
                        <input type="text" id="deliveramount" class="form-control col-xl-2" disabled value="₱50.00">
                    </div>
                </div>
                <div class="container">
                    <div class="row align-content-center">
                        <label for="promo">Voucher:</label>
                        
                <form action="<?php htmlspecialchars("PHP_SELF"); ?>" method="post"> 
                        <div class="input-group mb-1">
                            <select class="custom-select" id="inputGroupSelect02" name="voucher">
                            <?php
                                
                                $vouchersSql = "SELECT * FROM $userVoucher where `status` = 'Unused'";
                                $vouchersListQuery = mysqli_query($connection,$vouchersSql);
                                if($vouchersListQuery->num_rows > 0 ){
                                    while($row = $vouchersListQuery->fetch_assoc()){
                                        $salesID = $row['voucherID'];
                                        $salesAmount = $row['voucherName'];
                                        $salesDate = $row['voucherAmount'];
                                        $voucherDiscount = $row['voucherDiscount'];

                            ?>
                              <option value="<?php echo $salesID;?>"><?php echo $salesAmount." Discount: ".$voucherDiscount;?></option>
                            <?php
                                    }
                                }else{
                            ?>
                                <option value="">no vouchers available</option>
                            
                            <?php
                                }
                            ?>
                            </select>
                        </div>
                    </div>
                </div>
                <hr class="my-2 bg-warning">
                <!-- <form action="<?php htmlspecialchars("PHP_SELF"); ?>" method="post">  -->
                <input type="hidden" id="paymentMethod" name="paymentMethod" value="Cash On Delivery">
                <input type="hidden" id="totalPrice" name="totalPrice" value="<?php echo $totalPrice;?>">
                <button class="btn btn-danger w-100">Checkout</button>
                </form>
            </div>
        </div>
    </div>
    

    <!-- DO NOT CHANGE THIS -->
    <script>
        var t = document.getElementById("delete");
        var h = document.getElementById("modal2");
        var a = document.getElementById("edit");
        var v = document.getElementById("v_alert");
        function popup(){
            if (t.className === "popup"){
                t.className = "pop";
            }
            else{
                t.className = "popup";
            }
        }

        function popup_two(){
            if (h.className === "popup"){
                h.className = "pop";
            }
            else{
                h.className = "popup";
            }
        }

        function popup_three(){
            if (a.className === "popup"){
                a.className = "pop";
            }
            else{
                a.className = "popup";
            }
        }
        function popup_alert(){
            if (v.className === "popup"){
                v.className = "pop";
            }
            else{
                v.className = "popup";
            }
        }

        var table = document.getElementById("mytable");
  
        for(var i = 1; i < table.rows.length; i++)
        {
            table.rows[i].onclick = function()
            {
                 //rIndex = this.rowIndex;
                 //alert(this.cells[1].innerHTML);
                document.getElementById("pid_update").value = this.cells[0].innerHTML;
                document.getElementById("quantity_u").value = this.cells[3].innerHTML;
                document.getElementById("title_d").value = this.cells[2].innerHTML;
                document.getElementById("pid").value = this.cells[0].innerHTML;
                
            };
        }
    </script>
    <?php include('script.php');?>
</body>
</html>