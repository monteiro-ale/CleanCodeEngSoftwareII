<?php
$servidor = "localhost:3307";
$usuarioBd = "root";
$senhaBd = "";
$nomeBd = "first_db_xampp";
$conectar = mysqli_connect($servidor, $usuarioBd, $senhaBd, $nomeBd);


function verificaCamposVaziosNoLogin(){
	if(isset($_POST['enviar']) AND !empty($_POST['email']) AND !empty($_POST['senha'])){
		return true;
	}
return false;
}

function logar($conectar){

	if (verificaCamposVaziosNoLogin()) {				
		$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
		$senha = sha1($_POST['senha']);
		$query = "SELECT * FROM usuarios WHERE email = '$email' AND senha = '$senha' ";
		$msgErro = "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>E-mail ou senha inválidos</p>";
		$executar = mysqli_query( $conectar, $query );
		$resultado = mysqli_fetch_assoc($executar);
	
		if (!empty($resultado)) {
			iniciaSessao($resultado);
		}
	}
	echo $msgErro;
	
} //end logar

function iniciaSessao($resultado){
	
	session_start();
	$_SESSION['nome'] = $resultado['nome'];
	$_SESSION['img'] = $resultado['img'];
	$_SESSION['ativa'] = true;
	header('location:admin.php');
	
}

function deslogar(){
	session_start();
	session_unset();
	session_destroy();
	header("location: index.php");
}

function selecionar($conectar, $tabela, $where=1, $order="id"){
	$query = "SELECT * FROM $tabela WHERE $where ORDER BY $order" ;
	$executar = mysqli_query( $conectar, $query);
	$resultados = mysqli_fetch_all($executar, MYSQLI_ASSOC);	
	return $resultados;
}

function verificaCamposVaziosNoCadastro(){
	if(isset($_POST['cadastrar']) AND !empty($_POST['email']) AND !empty($_POST['senha'])){
		return true;
	}
	return false;
}

function validaDadosDeCadastro($nome, $email, $conectar){
	
	$erros = array();

	if (strlen($nome) < 3) {
		$erros[] = "<style scoped>p{text-align: center; color: #ff8a8a; font-weight:bolder; }</style><p>Preecha seu nome completo!</p>";
	}
	if (empty($email)) {
		$erros[] = "<style scoped>p{text-align: center; color: #ff8a8a; font-weight:bolder; }</style><p>Preencha um e-mail válido</p>";
	}
	if ($_POST['senha'] != $_POST['repetesenha']) {
		$erros[] = "<style scoped>p{text-align: center; color: #ff8a8a; font-weight:bolder; }</style><p>Senhas não são iguais!</p>";
	}
	if (!empty(verificaEmailsCadastrados($erros, $email, $conectar))){
		$erros = verificaEmailsCadastrados($erros, $email, $conectar);
	}
	return $erros;
}


function verificaEmailsCadastrados($erros, $email, $conectar){
	$queryEmail = "SELECT email FROM usuarios WHERE email = '$email'";
	$buscaEmail = mysqli_query( $conectar, $queryEmail);
	$resultEmail = mysqli_fetch_assoc( $buscaEmail );

	if ( !empty($resultEmail['email']) ) {
		 $erros[] = "<style scoped>p{text-align: center; color: #ff8a8a; font-weight:bolder; }</style><p>E-mail já cadastrado no sistema!</p>";
		return $erros;
	} 
}

function validaDadosDeArquivo(){
	$nomeArquivo = $_FILES['imagem']['name'][0];
	$tipo = $_FILES['imagem']['type'][0];
	$nomeTemporario = $_FILES['imagem']['tmp_name'][0];	
	$tamanho = $_FILES['imagem']['size'][0];

	if (!empty($nomeArquivo)AND !empty($tipo)AND !empty($nomeTemporario)AND !empty($tamanho)){
		return true;
	}
	return false;
}


function retornaQuery($imgAtualizada, $nome, $email, $senha){
	if(is_array($imgAtualizada)){
		foreach ($imgAtualizada as $erro){
			echo $erro;
			$query = "INSERT INTO usuarios (nome, email, senha, data_cadastro) VALUES ( '$nome', '$email', '$senha', NOW())";
		return $query;
		}
	}else{
		$query = "INSERT INTO usuarios (nome, email, senha, data_cadastro, img) VALUES ( '$nome', '$email', '$senha', NOW(), '$imgAtualizada' )";
		return $query;
	}
}

function validaInsercaoDeUsario($nome, $email, $senha, $conectar){

	if (validaDadosDeArquivo()) {
		$imgAtualizada = atualizaImagem($conectar);
		//Chamada de função que retorna a query
		$query = retornaQuery($imgAtualizada, $nome, $email, $senha);		
		}else{
		$query = "INSERT INTO usuarios (nome, email, senha, data_cadastro) VALUES ( '$nome', '$email', '$senha', NOW())";
		}
	$executar = mysqli_query($conectar, $query);

	if ($executar) {
		echo "Usuário inserido com sucesso!";
	}else{
		echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>Erro ao inserir Usuário</p>";
	}
}


function inserirUsuario($conectar){

	if ( verificaCamposVaziosNoCadastro() ){
		$nome = mysqli_real_escape_string($conectar, $_POST['nome']);
		$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
		$senha = sha1($_POST['senha']);
		$erroAoInserir = "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>Erro ao inserir Usuário</p>";
		//Chamada de função que retorna possíveis erros no cadastro	
		$erros = validaDadosDeCadastro($nome, $email, $conectar);

		if (empty($erros)) {	
			validaInsercaoDeUsario($nome, $email, $senha, $conectar);

		}foreach ($erros as $erro) {
				echo $erro . "<br>";
			}		
	}else{echo $erroAoInserir;}
}

function redirecionaPagina($redirecionar){
	if (!empty($redirecionar)) {
		//header("location: $redirecionar");
		echo "<script>window.location.href = '$redirecionar'</script>";
	}
}


function validaExclusaoUsuario($id, $conectar, $tabela, $where, $redirecionar){
	if ($id) {
		$query = "DELETE FROM $tabela WHERE id = $where";
		$executar = mysqli_query($conectar, $query);
		if ($executar) {
			echo "Usuário Deletado com Sucesso!";
			redirecionaPagina($redirecionar);
			
		}echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>Erro ao deletar!</p>";
	}else{
		echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>ID Inválido!</p>";
	}
}

function deletar($conectar, $tabela, $where, $redirecionar = ""){
	if (!empty($where)) {
		$id = filter_var($where, FILTER_VALIDATE_INT);
		validaExclusaoUsuario($id, $conectar, $tabela, $where, $redirecionar);
	}
}

function testaSenha($conectar, $where, $repeteSenha, $nome, $email, $senha, $data){
	if ($senha == $repeteSenha){
		$senha = sha1($senha);
		$query = "UPDATE usuarios SET nome = '".$nome."', email = '".$email."', senha = '".$senha."', data_cadastro = '".$data."' WHERE id = ".$where;
		$executar = mysqli_query($conectar, $query);
			if ($executar) {
				echo "Senha Atualizada com sucesso!<br>";
				}else{
					echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>Erro ao atualizar a senha</p>";
				}
		}else{
			echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>Senhas não são iguais!</p>";
		}
}

function validaAtualizacaoSenha($lstDados, $conectar,  $where){

	$id = $lstDados['id'];
	$nome = mysqli_real_escape_string($conectar, $lstDados['nome']);
	$email = mysqli_real_escape_string($conectar, $lstDados['email']);
	$data = $lstDados['data_cadastro'];
	$senha = $lstDados['senha'];
	$repeteSenha = $lstDados['repetesenha'];

	if (!empty($senha)) {
		testaSenha($conectar, $where, $repeteSenha, $nome, $email, $senha, $data);
	}
}

function validaAtualizacaoImagem($conectar, $imagem, $nome, $email, $data, $where){
	$imagemParaAtualizar = $imagem['name'][0];
	if (!empty($imagemParaAtualizar)) {
		$imgAtualizada = atualizaImagem($conectar);
		//Se o retorno da funçao for um array significa que são erros a serem mostrados
			if(is_array($imgAtualizada)){
				foreach ($imgAtualizada as $erro){
					echo $erro;
				}
			}else{
				$query = "UPDATE usuarios SET nome = '".$nome."', email = '".$email."', data_cadastro = '".$data."', img = '".$a."' WHERE id = ".$where;
				$executar = mysqli_query($conectar, $query);
				if ($executar) {
					echo "Imagem Atualizada com sucesso!<br>";
					}else{
						echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>Erro ao atualizar a imagem</p>";
					}
			}
		}
}


function consultaEmailNoBanco($conectar, $email, $id, $erros){
	$consultaEmail = "SELECT email FROM usuarios WHERE email = '" . $email . "' AND id <> ".$id;
	$executarBusca = mysqli_query($conectar, $consultaEmail);
	$resultado = mysqli_num_rows($executarBusca);
	if(!empty($resultado)){
		$erros[] = "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>E-mail já cadastrado!</p>";
		return $erros;
	} 
	return $erros;
}

function editar($conectar, $lstDados = array(), $where){

	if (!empty($where)) {
		$erros = array();
		$id = $lstDados['id'];
		$nome = mysqli_real_escape_string($conectar, $lstDados['nome']);
		$email = mysqli_real_escape_string($conectar, $lstDados['email']);
		$data = $lstDados['data_cadastro'];
		$imagem = $_FILES['imagem'];
		$erros = consultaEmailNoBanco($conectar, $email, $id, $erros);
		if (empty($erros)) {
			validaAtualizacaoSenha($lstDados, $conectar, $where);
			validaAtualizacaoImagem($conectar, $imagem, $nome, $email, $data, $where);

			$query = "UPDATE usuarios SET nome = '".$nome."', email = '".$email."', data_cadastro = '".$data."' WHERE id = ".$where;
			$executar = mysqli_query($conectar, $query);
				if ($executar) {
					echo "Atualizado com sucesso!<br>";

				}else{
					echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>Erro ao atualizar!</p>";
				}		
			}
	}else{
		foreach ($erros as $erro) {
		echo $erro . "<br>";
		}
	}	

}
 

function deletarImagem($conectar, $tabela, $where, $redirecionar = ""){
	if (!empty($where)) {
		//$id = is_int($where);
		$id = filter_var($where, FILTER_VALIDATE_INT);

		if ($id) {
			$query = "UPDATE usuarios SET img = NULL WHERE id = $where";
			$executar = mysqli_query($conectar, $query);
			if ($executar) {
				echo "Usuário Deletado com Sucesso!";
				if (!empty($redirecionar)) {
					//header("location: $redirecionar");
					echo "<script>window.location.href = '$redirecionar'</script>";
				}
				
			}else{
				echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>Erro ao deletar!</p>";
			}
		}else{
			echo "ID Inválido!";
		}
	}
}



function atualizaImagem($conectar){
	$nomeArquivo = $_FILES['imagem']['name'][0];
	$tipo = $_FILES['imagem']['type'][0];
	$nomeTemporario = $_FILES['imagem']['tmp_name'][0];	
	$tamanho = $_FILES['imagem']['size'][0];
	$tamanhoMaximo = 1024 * 1024 * 5;

	$caminho = "uploads/";
	$hoje = date('d-m-Y_h-i');
	$novoNome = $hoje."-".$nomeArquivo;

	if ($tamanho > $tamanhoMaximo) {
		$erros[] = "<style scoped>p{text-align: center; color: #ff8a8a; font-weight:bolder; }</style><p>Seu arquivo excede o tamanho máximo</p>";	
	}
	$extensao = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));		
	if($extensao != 'jpg' && $extensao != 'png' && $extensao != 'jpeg'){
	$erros[] = "<style scoped>p {text-align: center; color: #ff8a8a; font-weight:bolder; }</style><p>Extensão de arquivo não permitido!</p>";		
	}
	$typesPermitidos = ["application/pdf", "image/png", "image/jpg","image/jpeg"];
	if ( !in_array($tipo, $typesPermitidos) ) {
		$erros[] = "<style scoped>p {text-align: center; color: #ff8a8a; font-weight:bolder; }</style><p>Tipo de arquivo não permitido!</p>";
	}
	if (move_uploaded_file($nomeTemporario, $caminho.$novoNome)) {
		echo " ";
	}else{
		echo "<style scoped>p {text-align: center; color: #ff8a8a; font-weight:bolder; }</style><p>Erro ao Enviar o arquivo imagem</p>";
	}

	if(!empty($erros)){
		return $erros;
	}else{
		return $novoNome;
	}
}


// Alexandre Monteiro e Paola Fantinel