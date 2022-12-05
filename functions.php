<?php
$servidor = "localhost:3307";
$usuarioBd = "root";
$senhaBd = "";
$nomeBd = "first_db_xampp";
$conectar = mysqli_connect($servidor, $usuarioBd, $senhaBd, $nomeBd);

function logar($conectar){
	if ( isset($_POST['enviar']) AND !empty($_POST['senha']) AND !empty($_POST['email'])) {		
		
		$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
		$senha = sha1($_POST['senha']);
		$query = "SELECT * FROM usuarios WHERE email = '$email' AND senha = '$senha' ";
		//echo $query;
		$executar = mysqli_query( $conectar, $query );
		$resultado = mysqli_fetch_assoc($executar);
		//print_r($resultado);
		if (!empty($resultado)) {
			session_start();
			$_SESSION['nome'] = $resultado['nome'];
			$_SESSION['img'] = $resultado['img'];
			$_SESSION['ativa'] = true;
			header('location:admin.php');
		}else{
			echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>E-mail ou senha inválidos</p>";
		}
	}else{
		echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>E-mail ou senha inválidos</p>";
	}
} //end logar

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

function inserirUsuario($conectar){
	if ( isset($_POST['cadastrar']) AND !empty($_POST['email']) AND !empty($_POST['senha']) ){
		$nome = mysqli_real_escape_string($conectar, $_POST['nome']);
		$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
		$senha = sha1($_POST['senha']);

		$nomeArquivo = $_FILES['imagem']['name'][0];
		// print_r($_FILES['imagem']);
		//gera um nome unico pra imagem para não sobrescrever em caso de nomes iguais
		// $novoNome = uniqid(); - Por hora vamos manter a data no nome do arquivo
		$tipo = $_FILES['imagem']['type'][0];
		$nomeTemporario = $_FILES['imagem']['tmp_name'][0];	
		$pasta = "uploads/";
		$tamanho = $_FILES['imagem']['size'][0];
		$tamanhoMaximo = 1024 * 1024 * 5;
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

		$queryEmail = "SELECT email FROM usuarios WHERE email = '$email'";
		$buscaEmail = mysqli_query( $conectar, $queryEmail);
		$resultEmail = mysqli_fetch_assoc( $buscaEmail );

		if ( !empty($resultEmail['email']) ) {
		 	$erros[] = "<style scoped>p{text-align: center; color: #ff8a8a; font-weight:bolder; }</style><p>E-mail já cadastrado no sistema!</p>";
		} 
		

		if (empty($erros)) {
			
			if (!empty($nomeArquivo)AND !empty($tipo)AND !empty($nomeTemporario)AND !empty($tamanho)) {
				$a = atualizaImagem($conectar, $nomeArquivo,$tamanho,$tamanhoMaximo,$tipo,$nomeTemporario);
					if(is_array($a)){
						foreach ($a as $b){
							echo $b;
							$query = "INSERT INTO usuarios (nome, email, senha, data_cadastro) VALUES ( '$nome', '$email', '$senha', NOW())";
						}
					}else{$query = "INSERT INTO usuarios (nome, email, senha, data_cadastro, img) VALUES ( '$nome', '$email', '$senha', NOW(), '$a' )";}				
				}else{
				$query = "INSERT INTO usuarios (nome, email, senha, data_cadastro) VALUES ( '$nome', '$email', '$senha', NOW())";
				}
			$executar = mysqli_query($conectar, $query);
			// executa o insert
			// $query = "INSERT INTO usuarios (nome, email, senha, data_cadastro, img) VALUES ( '$nome', '$email', '$senha', NOW(), '$novoNome' )";
			// $executar = mysqli_query( $conectar, $query);
			if ($executar) {
				echo "Usuário inserido com sucesso!";
			}else{
				echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>Erro ao inserir Usuário</p>";
			}

		}else{
			foreach ($erros as $erro) {
				echo $erro . "<br>";
			}
		}
	}else{
		echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>Erro ao inserir Usuário!</p>";
	}

} //end function inserirUsuarios

function deletar($conectar, $tabela, $where, $redirecionar = ""){
	if (!empty($where)) {
		//$id = is_int($where);
		$id = filter_var($where, FILTER_VALIDATE_INT);

		if ($id) {
			$query = "DELETE FROM $tabela WHERE id = $where";
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
			echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>ID Inválido!</p>";
		}
	}
}

function editar($conectar, $lstDados = array(), $where){
	if (!empty($where)) {
		$erros = array();
		$id = $lstDados['id'];
		$nome = mysqli_real_escape_string($conectar, $lstDados['nome']);
		$email = mysqli_real_escape_string($conectar, $lstDados['email']);
		$data = $lstDados['data_cadastro'];

		$imagem = $_FILES['imagem'];
		$nomeArquivo = $_FILES['imagem']['name'][0];
		$tipo = $_FILES['imagem']['type'][0];
		$nomeTemporario = $_FILES['imagem']['tmp_name'][0];	
		$pasta = "uploads/";
		$tamanho = $_FILES['imagem']['size'][0];
		$tamanhoMaximo = 1024 * 1024 * 5;

		$senha = $lstDados['senha'];
		$repeteSenha = $lstDados['repetesenha'];

		$consultaEmail = "SELECT email FROM usuarios WHERE email = '" . $email . "' AND id <> ".$id;
		$executarBusca = mysqli_query($conectar, $consultaEmail);
		$resultado = mysqli_num_rows($executarBusca);
		//echo $resultado;

		// $consultaImagem = "SELECT img FROM usuarios WHERE id =".$id;
		// $executarBuscaImagem = mysqli_query($conectar, $consultaImagem);
		// $resultadoImagem = mysqli_fetch_assoc( $executarBuscaImagem);

		if(!empty($resultado)){
			$erros[] = "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>E-mail já cadastrado!</p>";
		}
			// var_dump($lstDados);
		if (empty($erros)) {
			if (!empty($senha)) {
				// echo"<br>Entrei na condiçao de senha não-vazia<br>";
				$senha = sha1($lstDados['senha']);
				// echo "<br>transformando a senha em sha1<br>";
				$query = "UPDATE usuarios SET nome = '".$nome."', email = '".$email."', senha = '".$senha."', data_cadastro = '".$data."' WHERE id = ".$where;
				$executar = mysqli_query($conectar, $query);
				if ($executar) {
				echo "Senha Atualizada com sucesso!<br>";
				}else{
					echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>Erro ao atualizar a senha</p>";
				}
			}
			
			if (!empty($imagem['name'][0])) {
				$a = atualizaImagem($conectar, $nomeArquivo,$tamanho,$tamanhoMaximo,$tipo,$nomeTemporario);
					if(is_array($a)){
						foreach ($a as $b){
							echo $b;
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
				}else{
				$query = "UPDATE usuarios SET nome = '".$nome."', email = '".$email."', data_cadastro = '".$data."' WHERE id = ".$where;
				$executar = mysqli_query($conectar, $query);
					if ($executar) {
						echo "Atualizado com sucesso!<br>";
						// var_dump($resultadoImagem);
						// var_dump($imagem);
					}else{
						echo "<style scoped>p{ color: #ff8a8a; font-weight:bolder; }</style><p>Erro ao atualizar!</p>";
						// var_dump($imagem);
					}
				}		
			}
		}	else{
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

function atualizaImagem($conectar,$nomeArquivo,$tamanho,$tamanhoMaximo,$tipo,$nomeTemporario){
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
		
	if ( !in_array( $tipo, $typesPermitidos )) {
		$erros[] = "<style scoped>p {text-align: center; color: #ff8a8a; font-weight:bolder; }</style><p>Tipo de arquivo não permitido!</p>";
	}
	if (move_uploaded_file($nomeTemporario, $caminho.$novoNome)) {
		echo " ";
	}else{
		echo "<style scoped>p {text-align: center; color: #ff8a8a; font-weight:bolder; }</style><p>Erro ao Enviar o arquivo imagem</p>";
	}
	// $query = "UPDATE usuarios SET nome = '".$nome."', email = '".$email."', senha = '".$senha."', data_cadastro = '".$data."', img = '".$novoNome."' WHERE id = ".$where;
	if(!empty($erros)){
		return $erros;
	}else{
		return $novoNome;
	}
}


// Alexandre Monteiro e Paola Fantinel