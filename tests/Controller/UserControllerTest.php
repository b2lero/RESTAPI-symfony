<?php

namespace App\Tests\Controller;

use App\Controller\UserController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserControllerTest
 *
 * @package App\Tests\Controller
 * @coversDefaultClass \App\Controller\UserController
 */
class UserControllerTest extends WebTestCase
{
    /** @var Client $client */
    private static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    /**
     * Implements testGetcUsers200
     * @covers ::getcUsers
     */
    public function testGetcUsers200()
    {

        self::$client->request(
            Request::METHOD_GET,
            UserController::USER_API_PATH
        );
        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertJson($response->getContent());
        $datosRecibidos = json_decode($response->getContent(), true);
        self::assertArrayHasKey('users', $datosRecibidos);
    }

    /**
     * Implements testGetcUser400
     * @covers ::getcUsers
     */
    public function testGetcUser404()
    {
        self::$client->request(
            request::METHOD_GET,
            UserController::USER_API_PATH . "/us"
        );
        /** @var Response $response */
        $response = self::$client->getResponse();
        $data = json_decode($response->getContent(), true);
        self::assertEquals($data['code'], $response->getStatusCode());

    }


    /**
     * @dataProvider providerId
     * @covers ::getUserUnique
     * @param $id_one
     * @return int
     */
    public function testGetUniqueUser($id_one): int
    {

        self::$client->request(
            request::METHOD_GET,
            UserController::USER_API_PATH . "/" . $id_one
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(
            Response::HTTP_OK, $response->getStatusCode()
        );

        self::assertJson($response->getContent());
        $dataResponse = json_decode($response->getContent(), true);
        self::assertArrayHasKey('user', $dataResponse);
        self::assertArrayHasKey('username', $dataResponse['user']);

        return $id_one;
    }

    /**
     * @dataProvider providerExistUser
     *@covers ::getUniqueUserByUsername
     */
    public function testGetUserByUsername($data)
    {

        $username = $data['username'];
        self::$client->request(
          request::METHOD_GET,
          UserController::USER_API_PATH . "/username/" . $username
        );

        /**@var Response $response*/
        $response = self::$client->getResponse();
        self::assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey("user", $data);
        self::assertEquals("200", $response->getStatusCode());
    }

    /**
     * @dataProvider providerDuplicateUser
     * @covers ::postUser
     */
    public function testPostUserDuplicate($data)
    {
        $datatest = $data;

        self::$client->request(
            Request::METHOD_POST,
            UserController::USER_API_PATH,
            [], [], [], json_encode($data)
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    /**
     * @depends      testGetUniqueUser
     * @dataProvider providerUser
     * @param $data
     * @return int $id
     */
    public function testPostUser201($data)
    {
        self::$client->request(
            Request::METHOD_POST,
            UserController::USER_API_PATH,
            [], [], [], json_encode($data)
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(
            Response::HTTP_CREATED,
            $response->getStatusCode()
        );

        self::assertJson($response->getContent());
        $dataResponse = json_decode($response->getContent(), true);
        self::assertArrayHasKey('user', $dataResponse);
        self::assertArrayHasKey('email', $dataResponse['user']);

    }

    /**
     * @dataProvider providerUserUpdates
     * @param $data
     */
    public function testUpdateUser201($data)
    {
        self::$client->request(
            Request::METHOD_PUT,
            UserController::USER_API_PATH . "/" . "1",
            [], [], [], json_encode($data)
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertJson($response->getContent());
        self::assertEquals(
            Response::HTTP_ACCEPTED, $response->getStatusCode()
        );
        $dataResponse = json_decode($response->getContent(), true);
        self::assertArrayHasKey('email', $dataResponse['user']);
        self::assertEquals($data['email'], $dataResponse['user']['email']);
    }


    /**
     * @covers ::deleteUser
     */
    public function testDeleteUser204()
    {
        self::$client->request(
            Request::METHOD_DELETE,
            UserController::USER_API_PATH . "/" . 108 //TODO <--- CHANGE
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

    }

    /**
     * @depends testGetUniqueUser
     * @param $id_one
     */
    public function testDeleteUser404($id_one)
    {
        self::$client->request(
            Request::METHOD_DELETE,
            UserController::USER_API_PATH . "/" . $id_one
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }


    public function providerUser()
    {
        $rdmNumber = rand(0, 10e3);
        return [
            [
                [
                    'username' => 'ener' . $rdmNumber,
                    'email' => 'dedefege' . $rdmNumber . '@live.com',
                    'password' => 'ffafasf',
                    'enable' => 'true'
                ]
            ]
        ];

    }

    public function providerId()
    {
        return [
            "iD" => ['102']
        ];
    }

    public function providerUserUpdates()
    {
        $rdmNumber = rand(0, 10e2);

        return [
            [
                [
                    'email' => 'phpUnitTest' . $rdmNumber . '@live.com'
                ]
            ]
        ];
    }

    public function providerDuplicateUser()
    {

        return [
            [
                [
                    "username" => "helene",
                    "email" => "helene@live.com",
                    "password" => "1234",
                    "enable" => "false"
                ]
            ]
        ];
    }

    public function providerExistUser()
    {

        return [
            [
                [
                    "username" => "helene",
                    "email" => "helene@live.com",
                    "password" => "1234",
                    "enable" => "false"
                ]
            ]
        ];
    }
}
