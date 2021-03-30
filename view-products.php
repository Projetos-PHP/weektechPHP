<?php 

    $id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT); // pega o valor do ID do produto passado via GET

   

    include_once('./connection.php');
    
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
    <link rel="shortcut icon" href="images/icon/favicon-16x16.png">
    <title>Visualizar o produto</title>
</head>
<body>

    <?php include_once('menu.php'); ?>


    <?php 

        $query_products  = "SELECT id, name, price, description, image FROM products WHERE id =:id LIMIT 1"; 
        $result_products = $conn->prepare($query_products);
        $result_products->bindParam(':id', $id, PDO::PARAM_INT); // é mais seguro passar o id do produto aqui do que dentro do select
        $result_products->execute();
        $row_product = $result_products->fetch(PDO::FETCH_ASSOC);

        extract($row_product);

        $price_rise = ($price * 0.50) + $price; // (preço atual * 50%) + preço atual - ex: (1 real * 0.50) + 1 = 150 reais
    
    ?>

    <div class="container">

        <h1 class="display-4 mt-5 mb-5"><?php echo $name; ?></h1>
        <div class="row">
            <div class="col-md-6">
                <img src='<?php echo "./images/products/$id/$image"; ?>' class="card-img-top" alt="">
            </div>
            <div class="col-md-6">
                <p>de R$ <?php echo number_format($price_rise, 2, ',', '.'); ?></p>
                <p>por R$ <?php echo number_format($price, 2, ',', '.'); ?></p>
                <p><a href="checkout-form.php?id=<?php echo $id; ?>" class="btn btn-primary">Comprar</a></p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 mt-5">
                <?php echo $description; ?>
            </div>
        </div>
        

    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>

</body>
</html>