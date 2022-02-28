<?php

namespace App\Controllers;

use App\DBConnection;
use App\Redirect;
use App\Session;
use App\View;


class UsersController
{
    public function home()
    {
var_dump($_SESSION['auth_id']);

        return  new View('Home/home');

    }

    public function index(): View
    {
      return  new View('Users/index');
    }

    public function show(array $vars): View
    {
        return  new View('Users/show', [
            'id' => $vars['id']
            ]);
    }


    public function register()
    {
        if (!Session::isAuthorized()) {
            return new View('Users/register');
        }
        else
        {
            return new Redirect('/?error=youarealreadyregistered');
        }
    }

    public function store():Redirect
    {
        //Validate form

        $userQueryCheck = DBConnection::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('users')
            ->where('email = ?')
            ->setParameter(0, $_POST['email'])
            ->executeQuery()
            ->fetchAssociative();

        if ($userQueryCheck != false) {
            return new Redirect('/users/register?error=emailalreadyexist');


        } else {
            $passwordHashed = password_hash($_POST['password'], PASSWORD_BCRYPT);
            DBConnection::connection()
                ->insert('users', [
                    'email' => $_POST['email'],
                    'password' => $passwordHashed
                ]);

            $userQuery = DBConnection::connection()
                ->createQueryBuilder()
                ->select('*')
                ->from('users')
                ->where('email = ?')
                ->setParameter(0, $_POST['email'])
                ->executeQuery()
                ->fetchAssociative();

            DBConnection::connection()
                ->insert('user_profiles', [
                    'user_id' => $userQuery['id'],
                    'name' => $_POST['name'],
                    'surname' => $_POST['surname'],
                    'birthday' => $_POST['birthday']
                ]);
            return new Redirect('/users');
        }

    }

    public function logIn()
    {
        if (!Session::isAuthorized()) {
            return new View('Users/register');
        }
        else {
            return new Redirect('/?error=youarealreadyregistered');
        }
    }

    public function validateLogIn()
    {
        //validate form

        $userQuery = DBConnection::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('users')
            ->where('email = ?')
            ->setParameter(0, $_POST['email'])
            ->executeQuery()
            ->fetchAssociative();

        if ($userQuery === false) {
            $status= new Redirect('/users/login?error=usernotfound');
        }
        else{
            $checkPwd = password_verify($_POST['password'], $userQuery['password']);

            if($checkPwd == false)
            {
                 return new Redirect('/users/login?error=wrongpassword');
            }
            else
            {
                $_SESSION["auth_id"] = $userQuery['id'];
                var_dump('loged in');
                return new Redirect('/');
            }
        }
    }

    public function logOut()
    {
        unset ($_SESSION["auth_id"]);
        return new Redirect('/');
    }

}