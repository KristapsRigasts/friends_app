<?php

namespace App\Controllers;

use App\DBConnection;
use App\Exceptions\FormValidationException;
use App\Redirect;
use App\Session;

use App\Validation\Errors;
use App\Validation\FormValidator;


use App\View;

class CommentsController
{
    public function create(array $vars)
    {
        if(!Session::isAuthorized()) {

            return new Redirect('/?error=youarenotauthorized');
        }
        else
        {
            return new View("Comments/create", [
                    'articleId'=> $vars['id'],
                    'userName'=> $_SESSION['name'],
                    'userId' => $_SESSION['userid'],
                'errors' => Errors::getAll()
                ]);
        }
    }

    public function store(array $vars): Redirect
    {

        try {
            $validator =(new FormValidator($_POST, [
                'comment' => ['required']
            ]));
            $validator->passes();

            DBConnection::connection()
                ->insert('comments', [
                    'article_id' => $vars['id'],
                    'user_id' => $_SESSION['userid'],
                    'comment' => $_POST['comment'],
                ]);

            return new Redirect('/articles/'. $vars['id']);

        } catch (FormValidationException $exception) {

            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['inputs'] = $_POST;

            return new Redirect('/articles/'. $vars['id']);
        }

    }

    public function delete(array $vars)
    {
        DBConnection::connection()
            ->delete('comments', ['id' => (int)$vars['cid']]);
        return new Redirect("/articles/{$vars['id']}");

    }
}