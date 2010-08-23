<?php
/**
 * Implements a PAM authentication class using the PEAR Auth
 * classes.
 * @see http://pear.php.net/package/Auth
 */
class api_pam {
    protected $config = array();
    protected $user = array();
    private $itoa64;
	private $iteration_count_log2;
	private $portable_hashes;
	private $random_state;

    /**
     * Constructor.
     */
    public function __construct() {
        //parent::__construct($opts);

        // TODO Shold session management be handled this way?
        $sessionid = session_id();
        if (empty($sessionid)) {
            session_start();
        }

        $this->config = api_config::getInstance();
        $this->request = api_request::getInstance();
        $this->db = api_database::factory();
    }

    public function login($username, $password) {
        if ($username === '' && $password === '') {
            $username = $this->request->getParam('username');
            $password = $this->request->getParam('password');
        }
//print_r($username);
        if (!empty($username)) {
            if ($this->checkAuth()) {
                $this->logout();
            }
            $crudColumns = $this->getConfiguredColumns();
//print_r($crudColumns);
            // $hash = $this->getOpt('hash');
            //$sql = 'SELECT SUBSTR('.$crudColumns['password'].',1,'.(int)$hash['saltLength'].')
            //    FROM '.$this->config->crud['crudTable'].'
            //    WHERE '.$crudColumns['username'].' = '.$this->db->quote($username);
            //$stmt = $this->db->prepare($sql);
            //$stmt->execute(array());
            //$salt = $stmt->fetchColumn();
            //if (empty($salt)) {
            //    api_log::log(api_log::INFO, 'Salt not found in Database');
            //}
            //$hashedPW = api_helpers_hashHelper::crypt_pass($password, $salt, $hash);

            $select = array();
            foreach ($crudColumns as $alias => $val) {
                $select[] = $val.' AS '.$alias;
            }
            $select = implode(' ,',$select);
            $sql = 'SELECT '.$select.' FROM '.$this->config->pam['table'].' WHERE '.$crudColumns['username'].' = :username';
//echo $sql;
            $stmt = $this->db->prepare($sql);

            $sqlParams = array(
                'username' => $username
            );
            $stmt->execute($sqlParams);
//echo "Here";
//print_r($sqlParams);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
//print_r($userData);
            // Check password
            if (empty($userData)) {
            //    api_log::log(api_log::INFO, 'Credentials not correct');
            //    echo "Credential not correct";
            } else if (!$this->checkPassword($password, $userData['password'])) {
  //              echo "Passwords wrong";
            } else {
                session_regenerate_id(true);
                unset($userData['password']);
    //            echo "<br />";
    //            print_r($this->config->appname);
    //            echo "<br />";
                //$_SESSION[$this->config->appname]['user'] = $userData;
                api_session::set('user', $userData);
            }
        }
        return $this->checkAuth();
    }

    public function logout() {
        /*if (isset($_SESSION[$this->config->appname])) {
            unset($_SESSION[$this->config->appname]);
        }*/
        if (api_session::get('user')) {
            api_session::destroy('user');
        }

        return false;
    }

    public function checkAuth() {
        //if (!empty($_SESSION[$this->config->appname]['user']['id'])) {
        if (api_session::get('user')) {
            //api_log::log(api_log::INFO, 'Session exists');
            //echo "Session exists";
            return true;
        }
        return false;
    }

    public function getUserName() {
        return $_SESSION[$this->config->appname]['user']['username'];
    }

    public function getUserId() {
        if (empty($_SESSION[$this->config->appname]['user']['id'])) {
            return false;
        }
        return $_SESSION[$this->config->appname]['user']['id'];
    }

    public function getAuthData() {
        if (empty($_SESSION[$this->config->appname])) {
            return array();
        }
        return $_SESSION[$this->config->appname];
    }

    protected function getConfiguredColumns() {
        $cfg = $this->config->pam;
        $columns = $cfg['mandatoryColumns'];

        /*if ($cfg['selectAdditional']) {
            $additional = $cfg['additionalColumns'];
            $columns = array_merge($additional,$columns);
        }*/

        return $columns;
    }

    //*************************
    // Check passwords

    function hashPassword($pass) {
        $random = $this->get_random_bytes(16);
        $salt = $this->gensalt_blowfish($random);
        $hash = crypt($pass, $salt);
        if (strlen($hash) == 60)
            return $hash;
        return '*';
    }
    
    private function get_random_bytes($count)
	{
		$output = '';
		if (is_readable('/dev/urandom') &&
		    ($fh = @fopen('/dev/urandom', 'rb'))) {
			$output = fread($fh, $count);
			fclose($fh);
		}

		if (strlen($output) < $count) {
			$output = '';
			for ($i = 0; $i < $count; $i += 16) {
				$this->random_state =
				    md5(microtime() . $this->random_state);
				$output .=
				    pack('H*', md5($this->random_state));
			}
			$output = substr($output, 0, $count);
		}

		return $output;
	}

    private function gensalt_blowfish($input) {
		# This one needs to use a different order of characters and a
		# different encoding scheme from the one in encode64() above.
		# We care because the last character in our encoded string will
		# only represent 2 bits.  While two known implementations of
		# bcrypt will happily accept and correct a salt string which
		# has the 4 unused bits set to non-zero, we do not want to take
		# chances and we also do not want to waste an additional byte
		# of entropy.
		$itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$output = '$2a$';
		$output .= '0';//chr(ord('0') + mt_rand(1,80) / 10); //$this->iteration_count_log2 / 10);
		$output .= chr(ord('7') + mt_rand(1,9) % 2); //$this->iteration_count_log2 % 10);
		$output .= '$';

		$i = 0;
		do {
			$c1 = ord($input[$i++]);
			$output .= $itoa64[$c1 >> 2];
			$c1 = ($c1 & 0x03) << 4;
			if ($i >= 16) {
				$output .= $itoa64[$c1];
				break;
			}

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 4;
			$output .= $itoa64[$c1];
			$c1 = ($c2 & 0x0f) << 2;

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 6;
			$output .= $itoa64[$c1];
			$output .= $itoa64[$c2 & 0x3f];
		} while (1);

		return $output;
	}

    function getSalt() {
        // Create blowfish bcrypt salt.
        // crypt needs a specialy formatted salt to choos the right algorithm
    //    $saltStr = '$2a$'mt_rand(2, 2).'$';
        while(strlen($saltStr) < 56) {
            $saltStr .= mt_rand();
        }
        $saltStr = substr($saltStr, 0, 56);
        $blowfishSalt = "$2a$" . $saltStr;
        return $blowfishSalt;
    }

    function checkPassword($input, $stored_hash) {
        if (crypt($input, $stored_hash) == $stored_hash) {
            return true;
        }
        return false;
    }


    // Utility functons
    function generatePassword($length=15) {
        $chars = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $secret = '';
        for($i = 0; $i < $length;  $i++) {
            $r = mt_rand(0, count($chars)-1);
            $secret .= $chars[$r];
        }
        return $secret;
    }
}

