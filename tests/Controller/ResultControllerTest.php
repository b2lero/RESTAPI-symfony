<?php

namespace App\Tests\Controller;

use App\Controller\ResultController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiPersonaControllerTest
 *
 * @package App\Tests\Controller
 * @coversDefaultClass \App\Controller\ResultController
 */
class ResultControllerTest extends WebTestCase
{
    /** @var Client $client */
    private static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    /**
     * @covers ::getUniqueResult
     */
    public function testGetUniqueResult()
    {
        self::$client->request(
            Request::METHOD_GET,
            ResultController::RESULT_API_PATH . "/" . 67 // TODO <-- CHANGE
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertJson($response->getContent());
        self::assertEquals(
            Response::HTTP_OK, $response->getStatusCode()
        );
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('time', $data['result']);
    }

    /**
     * @covers ::getUniqueResult
     */
    public function testGetUniqueResult404()
    {
        self::$client->request(
            Request::METHOD_GET,
            ResultController::RESULT_API_PATH. "/" . 99
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }


    /**
     * @dataProvider providerExistUserId
     * @covers ::getResultsByUser
     */
    public function testGetResultsByUserId($data){

        $user_id = $data['user_id'];

        self::$client->request(
            Request::METHOD_GET,
            ResultController::RESULT_API_PATH. "/user/" . $user_id
        );
        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey("user_results", $data);
        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @dataProvider providerResult
     * @covers ::postResult
     * @param $data
     */
    public function testPostResult($data)
    {
        $entrydata = $data;

        self::$client->request(
            Request::METHOD_POST,
            ResultController::RESULT_API_PATH,
            [],[],[], json_encode($data)
        );

        /** @var Response $response */
        $response =  self::$client->getResponse();
        self::assertEquals(
          Response::HTTP_CREATED,
          $response->getStatuscode()
        );

        self::assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('result', $data);
        self::assertArrayHasKey('user_id', $data['result']);
        self::assertEquals($entrydata['user_id'], $data['result']['user_id']['id']);
    }

    /**
     * @dataProvider providerFakeUserId
     * @param $data
     */
    public function testPostUserDontExist($data)
    {
        self::$client->request(
            Request::METHOD_POST,
            ResultController::RESULT_API_PATH,
            [],[],[], json_encode($data)
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        self::assertArrayNotHasKey('result', $data);

    }

    /**
     * @dataProvider providerIncompleteParams
     * @covers ::postResult
     */
    public function testPostResultIncompleteParams($data)
    {
        self::$client->request(
            Request::METHOD_POST,
            ResultController::RESULT_API_PATH,
            [],[],[], json_encode($data)
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $response->getStatusCode()
        );
    }


    /**
     * @covers ::getResults
     */
    public function testGetResults()
    {
        self::$client->request(
            Request::METHOD_GET,
            ResultController::RESULT_API_PATH
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertJson($response->getContent());
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('results', $data);
        self::assertArrayHasKey("user_id", $data['results'][6]);
    }

    /**
     * @covers ::deleteResult
     */
    public function testDeleteResult()
    {
        self::$client->request(
            Request::METHOD_DELETE,
            ResultController::RESULT_API_PATH. "/" . 65  // TODO <--CHANGE
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }


    /**
     * @dataProvider providerDataResultUpdate
     * @covers ::updateResult
     */
    public function testUpdateResult($data)
    {
        $entrydata = $data;

        self::$client->request(
          Request::METHOD_PUT,
          ResultController::RESULT_API_PATH . "/". 69 , // TODO <--CHANGE
          [],[],[],json_encode($data)
        );

        /** @var Response $response */
        $response = self::$client->getResponse();
        self::assertEquals(
          Response::HTTP_ACCEPTED, $response->getStatusCode()
        );
        self::assertJson($response->getContent());
        $data = json_decode($response->getContent(), true);
        self::assertEquals($data['result']['result'], $entrydata['result']);

    }

    /**
     * @dataProvider providerFakeUserId
     * @covers ::updateResult
     * @param $id
     */
    public function testUpdateResultUserNotFound($data)
    {

        self::$client->request(
            Request::METHOD_PUT,
            ResultController::RESULT_API_PATH. "/" . 77, //TODO <--- CHANGE
            [],[],[], json_encode($data)
        );

        /**@var Response $response */
        $response = self::$client->getResponse();
        $responseJsonToArray = json_decode($response->getContent(), true);
        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertContains($responseJsonToArray['message'], 'User id not found');
    }


    public function providerResult()
    {
        $rdmNumber = rand(0, 10e3);

        return [
            [
                [
                    'result' =>  $rdmNumber,
                    'user_id' => '75',
                    'time' => '2018-01-01 12:12:12'
                ]
            ]
        ];

    }

    public function providerFakeUserId()
    {
        $rdmUserId = rand(200, 10e3);
        return [
            [
                [
                    'result' =>  '111111',
                    'user_id' => $rdmUserId,
                    'time' => '2018-01-01 12:12:12'
                ]
            ]
        ];
    }

    public function providerIncompleteParams()
    {
        $rdmUserId = rand(200, 10e3);
        return [
            [
                [
                    'result' =>  null,
                    'user_id' => null,
                    'time' => '2018-01-01 12:12:12'
                ]
            ]
        ];
    }

    public function providerDataResultUpdate(){
        $rdmNumber = rand(0, 10e2);

        return [
            [
                [
                    'result' => 101 . $rdmNumber
                ]
            ]
        ];
    }

    public function providerExistUserId(){

        return [
            [
                [
                    'user_id' => 75
                ]
            ]
        ];
    }



}
