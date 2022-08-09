<?php 
namespace Hcode\Model;

use \Hcode\DB\Sql;
use Hcode\Model;
use Hcode\Mailer;
use Rain\Tpl;



class User extends Model {


	const SESSION = "User";
	const SECRET = "chave";

	protected $fields = [
		"iduser", "idperson", "deslogin", "despassword", "inadmin", "dtregister", "desperson", "nrphone", "desemail"
	];



	public static function login($login, $password):User
	{

		$db = new Sql();

		$results = $db->select("SELECT * FROM tb_users WHERE deslogin = :L", array(
			":L"=>$login
		));

		if (count($results) === 0) {
			throw new \Exception("Não foi possível fazer login.");
		}

		$data = $results[0];

		if (password_verify($password, $data["despassword"]) === true) {

			$user = new User();

			$user->setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;

		} else {

			throw new \Exception("Não foi possível fazer login.");

		}

	}

	public static function logout()
	{

		$_SESSION[User::SESSION] = NULL;

	}



	public static function verifyLogin($inadmin = true)
	{

		if (
			!isset($_SESSION[User::SESSION])
			|| 
			!$_SESSION[User::SESSION]
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
			||
			(bool)$_SESSION[User::SESSION]["iduser"] !== $inadmin
		) {
			
			header("Location: /admin/login/");
			
			exit;

		}

	}



	public static function listAll() {

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");

	}



	public function save() {

		$sql = new Sql();

		$results = $sql->select("CALL sp_users_save (:person, :login, :senha, :email, :tel, :inadmin);", array(
			":person"=>$this->getdesperson(),
			":login"=>$this->getdeslogin(),
			":senha"=>password_hash($this->getdespassword(), PASSWORD_DEFAULT, [
				"cost"=>12
			]),
			":email"=>$this->getdesemail(),
			":tel"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
			
		));
		
		$this->setData($results[0]);
	}



	public function get($iduser) {

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser", array(
			":iduser"=>$iduser
		));

		$this->setData($results[0]);

	}



	public function update() {

		$sql = new Sql();

		$results = $sql->select("CALL sp_usersupdate_save (:iduser, :person, :login, :senha, :email, :tel, :inadmin);", array(
			"iduser"=>$this->getiduser(),
			":person"=>$this->getdesperson(),
			":login"=>$this->getdeslogin(),
			":senha"=>$this->getdespassword(),
			":email"=>$this->getdesemail(),
			":tel"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		));
		
		$this->setData($results[0]);

	}


	public function delete() {

		$sql = new Sql();

		$sql->query("CALL sp_users_delete (:iduser)", array(
			"iduser"=>$this->getiduser()
		));
	}


	public static function getForgot($email) {


		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :email;", array(
			":email"=>$email
		));

		if (count($results) === 0)  {

			throw new \Exception("Não foi possível recuperar a senha");
			
		} else {

			$data = $results[0];

			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create (:iduser, :desip)", array(
				"iduser"=>$data['iduser'],
				"desip"=>$_SERVER["REMOTE_ADDR"]
			));

			if (count($results2) === 0 ) {

				throw new \Exception("Não foi possível recuperar a senha");

			} else {

				$dataRecovery = $results2[0];

				$method = "AES-128-CTR";
				$option = 0;
				$iv = 1234567890123456;
				

				$code = base64_encode(openssl_encrypt(json_encode($dataRecovery), $method, User::SECRET, $option, $iv));

				$link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code={$code}";

				$mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir senha", "forgot2", array(
					"name"=>$data['desperson'],
					"link"=>$link
				));

				$mailer->send();

				return $data;

			}
		}
	}



	public static function validForgotDecrypt($code) {

		$method = "AES-128-CTR";
		$option = 0;
		$iv = 1234567890123456;

		$data = openssl_decrypt(base64_decode($code), $method, User::SECRET, $option, $iv);

		//retorna objeto
		$idrecovery = json_decode($data);
		

		$sql = new Sql();

		$result = $sql->select("SELECT * FROM tb_userspasswordsrecoveries a 
					INNER JOIN tb_users b USING(iduser) 
					INNER JOIN tb_persons c USING(idperson)
					WHERE 
						a.idrecovery = :idrecovery
						AND dtrecovery IS NULL
						
						AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();", array(
							":idrecovery"=>$idrecovery->idrecovery
						));

		if (count($result) === 0) {

			throw new \Exception("Não foi possível recuperar senha");
		}
		 else {

			return $result[0];

		 }


	}


	public static function setForgotUsed($idrecovery) {

		$sql = new Sql();

		$sql->query("UPDATE tb_userpasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
			":idrecovery"=>$idrecovery
		));

	}


	public function setPassword($password) {

		$sql = new Sql();

		$sql->query("UPDATE tb_users SET despassword = :pass WHERE iduser= :iduser", array(
			":pass"=>$password,
			":iduser"=>$this->getiduser()
		));

	}

}
