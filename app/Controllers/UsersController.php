<?php

namespace App\Controllers;

use App\DBConnection;
use App\Exceptions\FormValidationException;
use App\Models\User;
use App\Redirect;
use App\Session;
use App\Validation\Errors;
use App\Validation\FormValidator;
use App\View;


class UsersController
{
    public function home(): View
    {
        if (!Session::isAuthorized())
        {
            return  new View('Home/home');
        }
        else {

            return new View('Home/home', ['userName'=> $_SESSION['name'], 'userId' =>$_SESSION['userid']]);
        }
    }

    public function index(): View
    {

        $userData =[];
        $invitedUserData=[];
        $userFriends=[];
        $userInvitationFrom =[];

        $usersQuery = DBConnection::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('user_profiles')
            ->where('user_id != ?')
            ->setParameter(0, (int) $_SESSION['userid'])
            ->executeQuery()
            ->fetchAllAssociative();

$userInvited=[];
        $userInvitedQuery=
            DBConnection::connection()
                ->createQueryBuilder()
                ->select('friend_id')
                ->from('friends')
                ->where('user_id = ?')
                ->setParameter(0, (int) $_SESSION['userid'])
                ->executeQuery()
                ->fetchAllAssociative();

foreach ($userInvitedQuery as $userInvitedData)
{
    $userInvited[]= $userInvitedData['friend_id'];
}
        $friendsInvited=[];

        $friendsInvitedQuery =
            DBConnection::connection()
                ->createQueryBuilder()
                ->select('user_id')
                ->from('friends')
                ->where('friend_id = ?')
                ->setParameter(0, (int) $_SESSION['userid'])
                ->executeQuery()
                ->fetchAllAssociative();

        foreach ($friendsInvitedQuery as $friendsInvitedData)
        {
            $friendsInvited[]= $friendsInvitedData['user_id'];
        }

        foreach ($usersQuery as $user)
        {

            if(in_array($user['user_id'], $userInvited) && in_array($user['user_id'],$friendsInvited) )
                {

                    $userFriends[]= new User($user['name'],$user['surname'], $user['user_id']);
                }
            else if(in_array($user['user_id'], $userInvited))
            {
                $invitedUserData[] = new User($user['name'],$user['surname'], $user['user_id']);
            }
            else if(in_array($user['user_id'],$friendsInvited))
            {
                $userInvitationFrom[] = new User($user['name'],$user['surname'], $user['user_id']);
            }
            else
            {
                $userData [] = new User($user['name'],$user['surname'], $user['user_id']);
            }

        }

      return  new View('Users/index',[
          'users' => $userData,
          'usersInvited' => $invitedUserData,
          'usersFriends' => $userFriends,
          'userInvitation' => $userInvitationFrom,
          'userName'=> $_SESSION['name'],
          'userId' => $_SESSION['userid'],
      ]);
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
            return new View('Users/register', [
                'inputs' => $_SESSION['inputs'] ?? [],
                'errors' => Errors::getAll()]);
        }
        else
        {
            return new Redirect('/?error=youarealreadyregistered');
        }
    }

    public function store():Redirect
    {
        //Validate form

        try {
            $validator =(new FormValidator($_POST, [
                'name' => ['required'],
                'surname' => ['required'],
                'email' => ['required'],
                'password' => ['required'],
                'birthday' => ['required'],
            ]));
            $validator->passes();

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
        }

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

        } catch (FormValidationException $exception) {

                $_SESSION['errors'] = $validator->getErrors();
                $_SESSION['inputs'] = $_POST;

            return new Redirect('/users/register');
        }

    }

    public function logIn(): View
    {
            return new View('Users/login',[
                'errors' => Errors::getAll(),
                'inputs' => $_SESSION['inputs'] ?? []
            ]);
    }

    public function validateLogIn(): Redirect
    {
        try {
            $validator =(new FormValidator($_POST, [
                'email' => ['required'],
                'password' => ['required']
            ]));
            $validator->passes();

        $userQuery = DBConnection::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('users')
            ->where('email = ?')
            ->setParameter(0, $_POST['email'])
            ->executeQuery()
            ->fetchAssociative();

        if ($userQuery === false) {
            return new Redirect('/users/login?error=usernotfound');
        }
        else{
            $checkPwd = password_verify($_POST['password'], $userQuery['password']);

            if($checkPwd == false)
            {
                 return new Redirect('/users/login?error=wrongpassword');
            }
            else
            {
                $userProfileQuery = DBConnection::connection()
                    ->createQueryBuilder()
                    ->select('*')
                    ->from('user_profiles')
                    ->where('user_id = ?')
                    ->setParameter(0, $userQuery['id'])
                    ->executeQuery()
                    ->fetchAssociative();

                $_SESSION["userid"] = $userQuery['id'];
                $_SESSION["name"] = $userProfileQuery['name'];
                $_SESSION["surname"] = $userProfileQuery['surname'];


                return new Redirect('/');
            }
        }} catch (FormValidationException $exception) {

            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['inputs'] = $_POST;

            return new Redirect('/users/login');
        }
    }

    public function logOut(): Redirect
    {
        unset ($_SESSION["userid"]);
        unset ($_SESSION["name"]);
        unset ($_SESSION["surname"]);
        return new Redirect('/');
    }

    public function invite(array $vars)
    {
        DBConnection::connection()
            ->insert('friends', [
                'friend_id' => $vars['id'],
                'user_id' => $_SESSION['userid'],

            ]);

        return new Redirect('/users');
    }

}