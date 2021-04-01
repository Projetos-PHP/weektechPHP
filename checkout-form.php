<?php 

    ob_start(); // serve para evitar erro de buffer. caso a hospedagem seja compartilhada devido ser limitada pode dar erro de buffer.

    $id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT); // pega o valor do ID do produto passado via GET

    if(empty($id)) { // se o $id vier vazio redireciona.
        header("Location: index.php");
        die('Error: Página não encontrada');
    }
   

    include_once('./connection.php');
    include_once('./config.php');
    
    // Pesquisar as informações do produto no banco de dados
    $query_products  = "SELECT id, name, price FROM products WHERE id =:id LIMIT 1"; 
    $result_products = $conn->prepare($query_products);
    $result_products->bindParam(':id', $id, PDO::PARAM_INT); // é mais seguro passar o id do produto aqui do que dentro do select
    $result_products->execute();

    if($result_products->rowCount() == 0) { // verifica se existe algum registro na tabela.
        header("Location: index.php");
        die('Error: Página não encontrada');
    } 

    $row_product = $result_products->fetch(PDO::FETCH_ASSOC);
    extract($row_product);

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

        // Receber os dados do formulário.
        $data = filter_input_array(INPUT_POST, FILTER_DEFAULT); // FILTER_DEFAULT - recebe todos os values do form como string.

        // Variavel msg recebe a mensagem de erro.
        $msg  = "";


        // VALIDAÇÃO DOS CAMPOS
        // Verifica se foi feito o submit no form
        if(isset($data['BtnPicPay'])) {

            $empty_input = false;
            $data        = array_map('trim', $data); // remove os espaços em branco do array onde ta os dados do form.

            if(in_array("", $data)) { // se no array dos dados tenha algum campo vazio.
                $empty_input = true;
                $msg         = "<div class='alert alert-danger' role='alert'>Erro: Necessário preencher todos os campos!</div>";
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $empty_input = true;
                $msg         = "<div class='alert alert-danger' role='alert'>Erro: Necessário preencher com um email válido!</div>";
            }


            // Se não existir nenhum erro no formulário faz a verificação.
            if(!$empty_input) {

                // Data para salvar no banco e enviar para o picpay.
                $data['created']  = date('Y-m-d H:i:s'); // cria no array dos dados a data atual.
                $data['due_date'] = date('Y-m-d H:i:s', strtotime($data['created'].'+3days') ); // acrescenta +3 dias na data atual e formata com date().

                $due_date = date(DATE_ATOM, strtotime($data['due_date'])); // strtotime() converte a $data['due_date'] para unix e depois DATE_ATOM converte para Formato ISO 8601 conforme a doc do picpay solicita.

                // Salvar os dados da compra no banco de dados.
                $query_pay_picpay = "INSERT INTO payments_picpays (first_name, last_name, cpf, phone, email, expires_at, product_id, created) VALUES (:first_name, :last_name, :cpf, :phone, :email, :expires_at, :product_id, :created)";
                $add_pay_picpay   = $conn->prepare($query_pay_picpay);
                $add_pay_picpay->bindParam(":first_name", $data['first_name'], PDO::PARAM_STR); // o usu do PDO::PARAM_STR é opcional. STR é de string.
                $add_pay_picpay->bindParam(":last_name", $data['last_name']);
                $add_pay_picpay->bindParam(":cpf", $data['cpf']);
                $add_pay_picpay->bindParam(":phone", $data['phone']);
                $add_pay_picpay->bindParam(":email", $data['email']);
                $add_pay_picpay->bindParam(":expires_at", $data['due_date']);
                $add_pay_picpay->bindParam(":product_id", $id);
                $add_pay_picpay->bindParam(":created", $data['created']);

                $add_pay_picpay->execute();

                if($add_pay_picpay->rowCount()) {
                    
                    $last_insert_id = $conn->lastInsertId(); // pega o ultimo registro inserido.
                  
                    $phone_form = str_replace("(", "", $data['phone']); // substitui o (ddd)
                    $phone_form = str_replace(")", "", $phone_form); // substitui o (ddd)

                    $dados_buy = [
                        "referenceId" => $last_insert_id,
                        "callbackUrl" => CALLBACKURL,
                        "returnUrl" => RETURNURL.$last_insert_id,
                        "value" => (double) $price,
                        "expiresAt" => $due_date,
                        "buyer" => [
                          "firstName" => $data['first_name'],
                          "lastName"  => $data['last_name'],
                          "document"  => $data['cpf'],
                          "email"     => $data['email'],
                          "phone"     => "+55 $phone_form"
                        ]
                    ];

                    // Iniciar cUrl
                    $ch = curl_init();

                    // URL de requisição do Picpay
                    curl_setopt($ch, CURLOPT_URL, 'https://appws.picpay.com/ecommerce/public/payments');

                    // Parâmetro de resposta
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    // Enviar o parametro referente ao SSL
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                    // Enviar dados da compra
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados_buy));

                    // Enviar os headers
                    $headers = [];
                    $headers[] = 'Content-Type: application/json';
                    $headers[] = 'x-picpay-token:'. PICPAYTOKEN;

                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                    // Realizar a requisição
                    $result = curl_exec($ch);

                    // Fechar conexão cUrl
                    curl_close($ch);

                    // Ler o conteudo da resposta
                    $data_result = json_decode($result);

                    
                        if( isset($data_result->code) AND $data_result->code != 200 ) {
                            
                            $msg  = "<div class='alert alert-danger' role='alert'>Erro: Tente novamente!</div>";

                        } else {

                            // Editar a compra informando dados que o PicPay retornou
                            $query_up_pay_picpay = "UPDATE payments_picpays SET payment_url = '".$data_result->paymentUrl."', qrcode = '".$data_result->qrcode->base64."', modified = NOW() WHERE id = $last_insert_id LIMIT 1";
                            $up_pay_picpay = $conn->prepare($query_up_pay_picpay);
                            $up_pay_picpay->execute();

                            ?>  
                                <div class="modal fade" id="picpay" tabindex="-1" aria-labelledby="picpayLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content text-center">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title" id="picpayLabel">Pague com o PicPay</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <h5 class="modal-title" id="picpayLabel">Abra com o PicPay em seu telefone e escaneie o código abaixo:</h5>
                                                <?php echo "<img src='".$data_result->qrcode->base64."'><br>"; ?>
                                                <p class="lead">Se tiver algum problema com a leitura do QR Code, atualize o aplicativo.</p>
                                                <p class="lead"><a href="https://meajuda.picpay.com/hc/pt-br/articles/360045117912-Quero-fazer-a-adi%C3%A7%C3%A3o-mas-a-op%C3%A7%C3%A3o-n%C3%A3o-aparece-para-mim-E-agora-" target="_blank">Saiba como atualizar o aplicativo</a></p>
                                            </div>
                                            <div class="modal-footer"></div>
                                        </div>
                                    </div>
                                </div>

                            <?php

                        }

                } else {
                    $msg  = "<div class='alert alert-danger' role='alert'>Erro: Tente novamente!</div>";

                }

            }

        }
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

                <?php 
                    if(!empty($msg)) { 
                        echo $msg;
                        $msg = "";
                    }
                ?>
            
                <form action="checkout-form.php?id=<?php echo $id; ?>" method="post">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="first_name">Primeiro Nome</label>
                            <input type="text" name="first_name" autofocus id="first_name" class="form-control" placeholder="Primeiro nome" value="<?php if(isset($data['first_name'])) { echo $data['first_name']; } ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="last_name">último Nome</label>
                            <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Último nome" value="<?php if(isset($data['last_name'])) { echo $data['last_name']; } ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="cpf">CPF</label>
                            <input type="text" name="cpf" id="cpf" class="form-control" placeholder="Somente o número do CPF" value="<?php if(isset($data['cpf'])) { echo $data['cpf']; } ?>" maxlength="14" oninput="maskCPF(this)">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="phone">Telefone</label>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="Telefone com o DDD" value="<?php if(isset($data['phone'])) { echo $data['phone']; } ?>" maxlength="14" oninput="maskPhone(this)">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">E-Mail</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="E-Mail" value="<?php if(isset($data['email'])) { echo $data['email']; } ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" name="BtnPicPay" value="Enviar">Enviar</button>
                </form>
            </div>
       </div>
        

    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>
    <script src="./js/custom.js"></script>
    
    <?php 
        if(isset($data_result->paymentUrl)) {
            
            ?>
            
                <script>
                    $(document).ready(function() {
                        $('#picpay').modal('show');
                    });
                </script>

            <?php
        
        }
    
    ?>
</body>
</html>