<?php

namespace App\Controllers;

use App\DBConnection;
use App\Models\Article;
use App\Models\User;
use App\Redirect;
use App\Session;
use App\View;

class ArticlesController
{
    public function index(): View
    {
        $articlesQuery = DBConnection::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('articles')
            ->orderBy('created_at','desc')
            ->executeQuery()
            ->fetchAllAssociative();

        //check if its not null then build object


        $articles =[];

        foreach($articlesQuery as $articleData)
        {
            $articles[] = new Article(
                $articleData['title'],
                $articleData['description'],
                $articleData['created_at'],
                $articleData['user_id'],
                $articleData['id']
            );
        }

        return  new View('Articles/index', ['articles' => $articles]);
    }

    public function show($vars): View
    {
        $articlesQuery = DBConnection::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('articles')
            ->where('id = ?')
            ->setParameter(0, (int) $vars['id'])
            ->executeQuery()
            ->fetchAssociative();

        $article = new Article(
            $articlesQuery['title'],
            $articlesQuery['description'],
            $articlesQuery['created_at'],
            $articlesQuery['user_id'],
            $articlesQuery['id']
        );

        $userQuery = DBConnection::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('user_profiles')
            ->where('id = ?')
            ->setParameter(0, $articlesQuery['user_id'])
            ->executeQuery()
            ->fetchAssociative();

        $user = new User($userQuery['name'], $userQuery['surname'],$userQuery['id'] );

        return  new View('Articles/show',['article' => $article, 'user' => $user]);
    }

    public function  create()
    {
        if(Session::isAuthorized()) {
            return new View('Articles/create');
        }
        else
        {
            return new Redirect('/?error=youarenotauthorized');
        }
    }

    public function store():Redirect
     {
         if(!Session::isAuthorized())
         {
             return new Redirect('/?error=youarenotauthorized');
         }
         else {
             //Validate form

             DBConnection::connection()
                 ->createQueryBuilder()
                 ->select('*')
                 ->from('users')
                 ->where('id = ?')
                 ->setParameter(0, (int)$_SESSION['auth_id'])
                 ->executeQuery()
                 ->fetchAssociative();


             DBConnection::connection()
                 ->insert('articles', [
                     'title' => $_POST['title'],
                     'description' => $_POST['description'],
                     'user_id' => $_SESSION['auth_id']
                 ]);

             return new Redirect('/articles');
         }

     }

     public function delete(array $vars): Redirect
     {
         if(!Session::isAuthorized())
         {
             return new Redirect('/?error=youarenotauthorized');
         }
         else {
             DBConnection::connection()
                 ->delete('articles', ['id' => (int)$vars['id']]);
             return new Redirect('/articles');
         }
     }

     public function edit(array $vars)
     {
         if(!Session::isAuthorized())
         {
             return new Redirect('/?error=youarenotauthorized');
         }
         else {
             $articlesQuery = DBConnection::connection()
                 ->createQueryBuilder()
                 ->select('*')
                 ->from('articles')
                 ->where('id = ?')
                 ->setParameter(0, (int)$vars['id'])
                 ->executeQuery()
                 ->fetchAssociative();


             $article = new Article(
                 $articlesQuery['title'],
                 $articlesQuery['description'],
                 $articlesQuery['created_at'],
                 $articlesQuery['user_id'],
                 $articlesQuery['id']
             );

             return new View('Articles/edit', ['article' => $article]);
         }
     }

     public function update(array $vars):Redirect
     {
         if(!Session::isAuthorized())
         {
             return new Redirect('/error=youarenotauthorized');
         }
         else {
             DBConnection::connection()
                 ->update('articles', [
                     'title' => $_POST['title'],
                     'description' => $_POST['description']
                 ], ['id' => (int)$vars['id']]);

             return new Redirect('/articles/' . $vars['id'] . '/edit');
         }

     }
}