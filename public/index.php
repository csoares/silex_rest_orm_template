<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \RedBeanPHP\R as R; // ORM

$app = new Application();


$app['db.connection.default.driver'] = "mysql";
$app['db.connection.default.host'] = "localhost";
$app['db.connection.default.name'] = "booksdb";
$app['db.connection.default.user'] = "root";
$app['db.connection.default.password'] = "root";


R::setup( 
"{$app['db.connection.default.driver']}:host={$app['db.connection.default.host']};dbname={$app['db.connection.default.name']}", 
$app['db.connection.default.user'], 
$app['db.connection.default.password'] 
);

// SELECT
$app->get('/obtain/{id}', function ($id) use ($app) {
    $book = R::load('books', $id );
    if(empty($book)) {
        return $app->json(array("result"=>"inexistant book id - $id"));
    }
    return $app->json($book);
})
->value("id", 1) 
->assert('id', '\d+'); 

$app->get('/books/{offset}/{limit}', function ($offset, $limit) use ($app) {
    $books = R::findAll('books', " LIMIT ? OFFSET ?", [(int)$limit,(int)$offset]);
    if(empty($books)) {
        return $app->json(array("result"=>"there isn't any book inside the database"));
    }
    return $app->json($books);
})
->value("offset", 0) 
->assert('offset', '\d+')
->value("limit", 10) 
->assert('limit', '\d+'); 


// UPDATE
$app->put('/edit/{id}',function (Request $request,$id) use ($app){
    $data = json_decode($request->getContent(), true);
    $book = R::findOne('books', 'id = ?', [(int)$id]);
    $book->import($data);
    /*
    $book->title=$data['title'];
    $book->author=$data['author'];
    $book->isbn=$data['isbn'];
    */
    R::store( $book );
    return $app->json($data, 200);
    
})
->assert('id', '\d+');

// INSERT
$app->post('/book', function(Request $request) use ($app) {
    $data = json_decode($request->getContent(), true); // load the received json data
    $book=R::dispense('books');
    $book->import($data);

    $id=R::store($book);
    
    $response = new Response('OK', 201);
    $response->headers->set('Location', "/book/$id");
    return $response;
});

// DELETE
$app->delete('/delete/{id}', function($id) use ($app) {
    $book=R::findOne('books', 'id = ?', [$id]);
    if($book < 1) {
          return new Response("Inexistant book id - $id", 404);
      }
      R::trash($book);
      return new Response(null, 204);
  })
  ->assert('id', '\d+'); 


$app['debug'] = true;
$app->run();
