<?php 

//namespace Hcode\Model;

//use \Hcode\Model;
use \Hcode\DB\Sql;


class Model {

	private $values = [];

	public function setData($data = array())
	{

		foreach ($data as $key => $value)
		{
			
			$this->{"set".$key}($value);
			
		}

	}

	public function __call($name, $args)
	{

		$method = substr($name, 0, 3);
		$fieldName = substr($name, 3, strlen($name));

		if (in_array($fieldName, $this->fields))
		{
			
			switch ($method)
			{

				case "get":
					return $this->values[$fieldName];
				break;

				case "set":
					$this->values[$fieldName] = $args[0];
				break;

			}

		}

	}

	public function getValues()
	{

		return $this->values;

	}

}

class User extends Model {

	const SESSION = "User";

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
			//":person"=>$this->getdesperson(),
			//":login"=>$this->getdeslogin(),
			//":senha"=>$this->getdespassword(),
			//":email"=>$this->getdesemail(),
			//":tel"=>$this->getnrphone(),
			//":inadmin"=>$this->getinadmin()
			":person"=>$_POST['desperson'],
			":login"=>$_POST['deslogin'],
			":senha"=>$_POST['despassword'],
			":email"=>$_POST['desemail'],
			":tel"=>$_POST['nrphone'],
			":inadmin"=>$_POST['inadmin']
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
}

?>