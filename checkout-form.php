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
    <title>Formulário de Checkout</title>
</head>
<body>

    <?php include_once('menu.php'); ?>

    <?php 

        $query_products  = "SELECT id, name, price FROM products WHERE id =:id LIMIT 1"; 
        $result_products = $conn->prepare($query_products);
        $result_products->bindParam(':id', $id, PDO::PARAM_INT); // é mais seguro passar o id do produto aqui do que dentro do select
        $result_products->execute();
        $row_product = $result_products->fetch(PDO::FETCH_ASSOC);

        extract($row_product);

        $price_rise = ($price * 0.50) + $price; // (preço atual * 50%) + preço atual - ex: (1 real * 0.50) + 1 = 150 reais

    ?>

    <div class="container">

       <div class="py-5 text-center">
            <img src="images/logo/logo.png" width="180" class="d-block mx-auto mb-4" alt="">
            <h2>Formulário de Pagamento</h2>
            <p>Checkout de pagamento do produto</p>
       </div>

       <div class="row mb-5">
            <div class="col-md-8">
                <h3><?php echo $name; ?></h3>
            </div>

            <div class="col-md-4">
                <div class="mb-1 text-muted"><?php echo number_format($price, 2, ",", "."); ?></div>
            </div>
       </div>   
        <hr>
       <div class="row mb-5">
            <div class="col-md-12">
                <h4 class="mb-3">Informações pessoais</h4>
            
                <form action="">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="first_name">Primeiro Nome</label>
                            <input type="text" name="last_name" autofocus id="first_name" class="form-control" placeholder="Primeiro nome" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="last_name">último Nome</label>
                            <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Último nome" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="cpf">CPF</label>
                            <input type="text" name="cpf" id="cpf" class="form-control" placeholder="Somente o número do CPF" required maxlength="14" oninput="maskCPF(this)">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="phone">Telefone</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="Telefone com o DDD" required maxlength="14" oninput="maskPhone(this)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">E-Mail</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="E-Mail">
                    </div>
                    <button type="submit" class="btn btn-primary">Enviar</button>
                </form>
            </div>
       </div>
        

    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>
    <script src="./js/custom.js"></script>
</body>
</html>