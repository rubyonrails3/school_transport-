<?php

class User_model extends CI_Model {

  public $id;
  public $first_name;
  public $last_name;
  public $username;
  public $salt;
  public $password;
  public $encrypted_password;
  public $type;
  public $phone;
  public $allowed;
  public $attending;
  public $token;
  public $roll_number;
  public $latitude;
  public $longitude;


  public function __construct() {
    parent::__construct();
  }

  public static function initialize($user_row) {
    $user = new self();
    $user->setObject($user_row);
    return $user;
  }


  public function setObject( $user_row) {

    foreach($user_row as $key => $value) {
      if( property_exists( $this, $key ) ) {
        $this->$key = $value;
      }
    }
  }


  public function hasRoute() {
    return !!$this->db->where('user_id', $this->id)->get('driver_routes')->row();
  }


  public function create() {
    $this->id = $this->db->insert_id();
    $parameters = $this->input->post();
    foreach($parameters as $key => $value) {
      if( property_exists( $this, $key ) ) {
        $this->$key = $value;
      }
    }
    $this->encrypted_password = $this->generateHashedPassword($this->input->post('password'), TRUE);
    $this->db->insert('users', $this);
    $this->id = $this->db->insert_id();
    return isset($this->id) ? $this : NULL;
  }

  public function update() {
    $parameters = $this->input->input_stream();
    foreach($parameters as $key => $value) {
      if( property_exists( $this, $key ) ) {
        $this->$key = $value;
      }
    }
    if($this->input->input_stream('password') != NULL) {
      $this->encrypted_password = $this->generateHashedPassword($this->input->input_stream('password'), TRUE);
    }
    $this->db->where('id', $this->id);
    $this->db->update('users', $this);
    return $this->db->affected_rows();
  }

  public function destroy() {
    $this->db->where('id', $this->id);
    $this->db->delete('users');
    return $this->db->affected_rows();
  }

  public function find($id) {
    $user = $this->db->where('id', $id)->get('users')->row();
    if( $user != NULL ) { 
      $this->setObject($user);
    } else {
      return NULL;
    }
    return $this;
  }

  public function find_by($field, $value) {
    return $this->db->where($field, $value)->get('users')->row();
  }

  public function isAdmin() {
    return $this->type == 'admin';
  }

  public function isDriver() {
    return $this->type == 'driver';
  }

  public function isStudent() {
    return $this->type == 'student';
  }

  private function set($user) {
    $this->setObject( $user );
  }

  public function asJson() {

    $json = array(
      'id' => $this->id,
      'first_name' => $this->first_name,
      'last_name' => $this->last_name,
      'username' => $this->username,
      'type' => $this->type,
      'phone' => $this->phone,
      'token' => $this->token,
      'password' => $this->password,
      'latitude' => $this->latitude,
      'longitude' => $this->longitude
    );

    if( $this->type == 'student' ) {
      $json['attending'] = $this->attending;
      $json['allowed'] = $this->allowed;
      $json['roll_number'] = $this->roll_number;
    }
    return $json;
  }


 public function generateHashedPassword($password, $isNew) {
   $options = [
     'cost' => 11,
     'salt' => $this->getSalt($isNew),
   ];
   return password_hash($password, PASSWORD_BCRYPT, $options);
 }

  private function getSalt($isNew) {
    if($isNew == FALSE) {
      return $this->salt;
    } else {
      $length = 22;
      $this->salt = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
      return $this->salt;
    }
  }


  public function authenticate($username, $password) {
    $user = $this->db->where('username', $username)->get('users')->row();
    if( $user != NULL ) {
      $this->set($user);
      if($this->generateHashedPassword($password, FALSE) == $user->encrypted_password) {
        return $this;
      } else {
        return NULL;
      }
    }
  }

}
