<?php

namespace WebsocketApi\handlers\handlersBase;

use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Configuration as JWTConfiguration ;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\ValidAt;
use Lcobucci\JWT\Validation\Constraint\IdentifiedBy;

class PrivateHandler extends DefaultHandler
{
  private $jwt_secret = 'мой_секретный _ключ';
  private $jwt_identifier = '5j1g54f73v8dl';
  private $jwt_valid = '+5 min';

  private $passwordHashCost = 13;

  protected $authMethods = [];

  protected function authorization(string $method, string $token)
  {

    if (!in_array($method, $this->authMethods)) {
      return true;
    }

    $config = JWTConfiguration::forSymmetricSigner(
      new Sha256(),
      InMemory::plainText($this->jwt_secret)
    );

    $token = $config->parser()->parse($token);

    if (
      !$config->validator()->validate(
        $token,
        new SignedWith(
          new Sha256(),
          InMemory::plainText($this->jwt_secret)
        ),
        new ValidAt(new FrozenClock(new \DateTimeImmutable())),
        new IdentifiedBy($this->jwt_identifier)
      )
    ) {
      throw new \Exception('Токен доступа не валиден или просрочен');
    }
    $user_id = $token->claims()->get('uid');

    $this->user = $this->getUser((int)$user_id);

    echo 'user: ' . json_encode($this->user) . "\r\n";

    if (!$this->user) {
      throw new \Exception('Пользователь не найден');
    }
      
    return true;
  }

  protected function getUser(int $user_id)
  {
    return null;
  }

  protected function issueAccessToken(int $user_id)
  {
    $config = JWTConfiguration::forSymmetricSigner(
      new Sha256(),
      InMemory::plainText($this->jwt_secret)
    );
    $now   = new \DateTimeImmutable();
    $token = $config->builder()
      // Configures the issuer (iss claim)
      //->issuedBy('http://example.com')
      // Configures the audience (aud claim)
      //->permittedFor('http://example.org')
      // Configures the id (jti claim)
      ->identifiedBy($this->jwt_identifier)
      // Configures the time that the token was issue (iat claim)
      ->issuedAt($now)
      // Configures the expiration time of the token (exp claim)
      ->expiresAt($now->modify($this->jwt_valid))
      // Configures a new claim, called "uid"
      ->withClaim('uid', $user_id)
      // Configures a new header, called "foo"
      //->withHeader('foo', 'bar')
      // Builds a new token
      ->getToken($config->signer(), $config->signingKey());
    
    return $token->toString();
  }

  protected function validatePassword($password, $hash)
  {
    if (!is_string($password) || $password === '') {
      throw new \Exception('Password must be a string and cannot be empty.');
    }

    if (!preg_match('/^\$2[axy]\$(\d\d)\$[\.\/0-9A-Za-z]{22}/', $hash, $matches)
        || $matches[1] < 4
        || $matches[1] > 30
    ) {
        throw new \Exception('Hash is invalid.');
    }

    if (function_exists('password_verify')) {
        return password_verify($password, $hash);
    }

    $test = crypt($password, $hash);
    $n = strlen($test);
    if ($n !== 60) {
        return false;
    }

    return $this->compareString($test, $hash);
  }

  protected function generatePasswordHash($password)
  {
    if (function_exists('password_hash')) {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => $this->passwordHashCost]);
    }

    $salt = $this->generateSalt($this->passwordHashCost);
    $hash = crypt($password, $salt);
    // strlen() is safe since crypt() returns only ascii
    if (!is_string($hash) || strlen($hash) !== 60) {
        throw new \Exception('Unknown error occurred while generating hash.');
    }

    return $hash;
  }

  protected function generateSalt()
  {
    // Get a 20-byte random string
    $rand = $this->generateRandomKey(20);
    // Form the prefix that specifies Blowfish (bcrypt) algorithm and cost parameter.
    $salt = sprintf('$2y$%02d$', $this->passwordHashCost);
    // Append the random salt data in the required base64 format.
    $salt .= str_replace('+', '.', substr(base64_encode($rand), 0, 22));

    return $salt;
  }

  protected function generateRandomKey($length = 32)
  {
    if (!is_int($length)) {
        throw new \Exception('First parameter ($length) must be an integer');
    }

    if ($length < 1) {
        throw new \Exception('First parameter ($length) must be greater than 0');
    }

    // always use random_bytes() if it is available
    if (function_exists('random_bytes')) {
        return random_bytes($length);
    }

    // Create random token
    $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    
    $max = strlen($string) - 1;
    
    $token = '';
    
    for ($i = 0; $i < $length; $i++) {
      $token .= $string[mt_rand(0, $max)];
    }	
    
    return $token; 
  }

  protected function compareString($expected, $actual)
  {
    if (!is_string($expected)) {
        throw new \Exception('Expected expected value to be a string, ' . gettype($expected) . ' given.');
    }

    if (!is_string($actual)) {
        throw new \Exception('Expected actual value to be a string, ' . gettype($actual) . ' given.');
    }

    if (function_exists('hash_equals')) {
        return hash_equals($expected, $actual);
    }

    $expected .= "\0";
    $actual .= "\0";
    $expectedLength = mb_strlen($expected, '8bit');
    $actualLength = mb_strlen($actual, '8bit');
    $diff = $expectedLength - $actualLength;
    for ($i = 0; $i < $actualLength; $i++) {
        $diff |= (ord($actual[$i]) ^ ord($expected[$i % $expectedLength]));
    }

    return $diff === 0;
  }
}