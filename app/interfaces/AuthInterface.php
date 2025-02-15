<?php 
namespace App\interfaces;

interface AuthInterface
{
    public function register($request);
    public function login($request);
    public function profile($id);
    public function selectPassword($email);
    public function deletePassword($email);
    public function changePassword($request);
}