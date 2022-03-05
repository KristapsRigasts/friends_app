<?php

namespace App\Controllers;

use App\DBConnection;
use App\Exceptions\FormValidationException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Article;
use App\Models\Comments;
use App\Models\User;
use App\Redirect;
use App\Session;
use App\Validation\FormValidator;
use App\Validation\Errors;
use App\View;
use Doctrine\DBAL\Exception;

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

        return  new View('Articles/index', [
            'articles' => $articles,
            'userName'=> $_SESSION['name'] ??[],
            'userId' => $_SESSION['userid'] ??[]
            ]);
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
            ->where('user_id = ?')
            ->setParameter(0, $articlesQuery['user_id'])
            ->executeQuery()
            ->fetchAssociative();


        $articleLikesQuery = DBConnection::connection()
            ->createQueryBuilder()
            ->select('COUNT(id)')
            ->from('article_likes')
            ->where('article_id = ?')
            ->setParameter(0, (int) $vars['id'])
            ->executeQuery()
            ->fetchOne();

        $commentsQuery = DBConnection::connection()
            ->createQueryBuilder()
            ->select('*')
            ->from('comments')
            ->where('article_id = ?')
            ->orderBy('created_at','desc')
            ->setParameter(0, $vars['id'])
            ->executeQuery()
            ->fetchAllAssociative();

        $comments =[];

        foreach ($commentsQuery as $comment)
        {
            $userQueryData = DBConnection::connection()
                ->createQueryBuilder()
                ->select('*')
                ->from('user_profiles')
                ->where('user_id = ?')
                ->setParameter(0, $comment['user_id'])
                ->executeQuery()
                ->fetchAssociative();


            $comments [] = [$userQueryData['name'],
                $userQueryData['surname'],
                $userQueryData['user_id'],
                $comment['comment'],
                $comment['created_at'],
                $comment['id']];
        }

        $user =new User($userQuery['name'],$userQuery['surname']);

        return  new View('Articles/show',[
            'article' => $article,
            'user' => $user,
            'articleLikes' => $articleLikesQuery,
            'comments' => $comments ?? [],
            'userName'=> $_SESSION['name'],
            'userId' => $_SESSION['userid']
        ]);
    }

    public function  create()
    {
        if(Session::isAuthorized()) {
            return new View('Articles/create', [
                'userName'=> $_SESSION['name'],
                'userId' => $_SESSION['userid'],
                'errors' => Errors::getAll(),
                'inputs' => $_SESSION['inputs'] ?? []
            ]);
        }
        else
        {
            return new Redirect('/?error=youarenotauthorized');
        }
    }

    public function store(): Redirect
     {
         try {
             $validator =(new FormValidator($_POST, [
                 'title' => ['required', 'min:3'],
                 'description' => ['required']
             ]));
             $validator->passes();

             DBConnection::connection()
                 ->createQueryBuilder()
                 ->select('*')
                 ->from('users')
                 ->where('id = ?')
                 ->setParameter(0, (int)$_SESSION['userid'])
                 ->executeQuery()
                 ->fetchAssociative();

             DBConnection::connection()
                 ->insert('articles', [
                     'title' => $_POST['title'],
                     'description' => $_POST['description'],
                     'user_id' => $_SESSION['userid']
                 ]);

             return new Redirect('/articles');

         } catch (FormValidationException $exception) {

             $_SESSION['errors'] = $validator->getErrors();
             $_SESSION['inputs'] = $_POST;

             return new Redirect('/articles/create');
         }

     }

     public function delete(array $vars): Redirect
     {

             DBConnection::connection()
                 ->delete('articles', ['id' => (int)$vars['id']]);
             return new Redirect('/articles');

     }

     public function edit(array $vars)
     {
         if(!Session::isAuthorized())
         {
             return new Redirect('/?error=youarenotauthorized');
         }
         else {
             try {
                 $articlesQuery = DBConnection::connection()
                     ->createQueryBuilder()
                     ->select('*')
                     ->from('articles')
                     ->where('id = ?')
                     ->setParameter(0, (int)$vars['id'])
                     ->executeQuery()
                     ->fetchAssociative();

                 if(!$articlesQuery )
                 {
                     throw new ResourceNotFoundException("Article with id {$vars['id']} not found");
                 }

             $article = new Article(
                 $articlesQuery['title'],
                 $articlesQuery['description'],
                 $articlesQuery['created_at'],
                 $articlesQuery['user_id'],
                 $articlesQuery['id']
             );

             return new View('Articles/edit', [
                 'article' => $article,
                 'userName'=> $_SESSION['name'],
                 'userId' => $_SESSION['userid']
             ]);
         } catch (ResourceNotFoundException $exception) {
                 var_dump($exception->getMessage());
                 return new View('404');
             }

         }
     }

     public function update(array $vars):Redirect
     {
             DBConnection::connection()
                 ->update('articles', [
                     'title' => $_POST['title'],
                     'description' => $_POST['description']
                 ], ['id' => (int)$vars['id']]);

             return new Redirect('/articles/' . $vars['id'] . '/edit');

     }

     public function like(array $vars): Redirect
     {
             $articleLikeQuery=DBConnection::connection()
                 ->createQueryBuilder()
                 ->select('id')
                 ->from('article_likes')
                 ->where('article_id = ?', 'user_id = ?')
                 ->setParameter(0, (int) $vars['id'])
                 ->setParameter(1, (int) $_SESSION['userid'])
                 ->executeQuery()
                 ->fetchAllAssociative();

             if(!empty($articleLikeQuery))
             {
                 return new Redirect('/articles/'. $vars['id'] . '?error=youalreadylikedthispost');

             }
             else {
                 DBConnection::connection()
                     ->insert('article_likes', [
                         'user_id' => $_SESSION['userid'],
                         'article_id' => $vars['id'],
                     ]);
                 return new Redirect('/articles/'. $vars['id']);
             }

         }


}