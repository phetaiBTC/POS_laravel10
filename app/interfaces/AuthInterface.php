<?php 
namespace App\interfaces;

interface AuthInterface
{
    public function register($request);
    public function login($request);
    public function profile();
    public function logout();
    public function forgotPassword($request);
    public function resetPassword($request);
}