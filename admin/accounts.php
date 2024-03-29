<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <?php include('css.php');?>
    <title>Manage the Accounts</title>
</head>
<body>
    <header>
        <?php include('nav.php');?>
    </header>
    <section class="jumbotron bg-transparent">
        <h1 class="display-4">Manage your customer Accounts here</h1>
        
        <hr class="my-4">
        <div class="table table-responsive">
            <table id="table" class="table table-responsive table-bordered w-100">
                <tr class="thead-dark text-center">
                    <th>
                        Customer Email
                    </th>
                    <th>
                        Customer Name
                    </th>
                    <th>
                        Address
                    </th>
                    <th>
                        Contact Number
                    </th>
                    <th>
                        Status
                    </th>
                </tr>
                <div id="card" class="col-xl-9  mt-5 overflow-hidden">
            <div class="container">
                <div class="row justify-content-center">
                <?php
                    include('../connection.php');
                    $getItems = "SELECT * FROM useraccounts INNER JOIN userdetails ON useraccounts.userEmail = userdetails.email where useraccounts.userType = 'user'";
                    $result = mysqli_query($connection, $getItems);
                    if(mysqli_num_rows($result) > 0){
                        while($row = mysqli_fetch_array($result)){
                    ?>
                        <tr class="text-center">
                            <td>
                                <?php echo $row['userEmail']; ?>
                            </td>
                            <td>
                                <?php $name = $row['fName']." ".$row['lName'];
                                echo $name; ?>
                            </td>
                            <td>
                                <?php echo $row['address']; ?>
                            </td>
                            <td>
                                <?php echo $row['contactNo']; ?>
                            </td>
                            <td>
                               <?php 
                                 if($row['status'] === "Inactive"){
                                    echo "<button id='inactive' class='btn btn-danger'><i>".$row['status']."</i></button>";
                                } else{
                                    echo "<button id='active' class='btn btn-primary'><i>".$row['status']."</i></button>";
                                }
                                ?>
                            </td>
                            
                        </tr> 
                    <?php 
                        }}
                    ?> 
                </div>
            </div>
        </div>

            </table>
        </div>
    </section>]
    <?php include('script.php');?>
</body>
</html>