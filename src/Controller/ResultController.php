<?php

namespace App\Controller;

use App\Entity\Result;
use App\Entity\User;

use PHPUnit\Runner\Exception;
use PHPUnit\Util\Json;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class ResultController
 * @Route(path=ResultController::RESULT_API_PATH, name="api_result_")
 * @package App\Controller
 */
class ResultController extends AbstractController
{

    public const RESULT_API_PATH = "/api/v1/results";


    /**
     * @Route(path="", name="getc_results", methods={Request::METHOD_GET})
     * @return JsonResponse
     */
    public function getResults(): Response
    {
        $em = $this->getDoctrine()->getManager();
        $results = $em->getRepository(Result::class)->findAll();

        if (null == $results) {
            return $this->error404();
        }

        return new JsonResponse(
            [
                "results" => $results
            ]
        );

    }


    /**
     * @Route(path="/{id}", name="get_result", methods={Request::METHOD_GET})
     * @param Result|null $result
     * @return JsonResponse
     */
    public function getUniqueResult(?Result $result)
    {
        return(null == $result)
            ? $this->error404()
            : new JsonResponse(["result" => $result], Response::HTTP_OK);
    }


    /**
     * @Route(path="/user/{user_id}")
     * @param $user_id
     * @return JsonResponse
     */
    public function getResultsByUser($user_id){

        $em = $this->getDoctrine()->getManager();
        $userResults = $em->getRepository(Result::class)->findBy(["user_id" => $user_id]);

        return(null == $userResults)
            ? $this->error404()
            : new JsonResponse(['user_results' => $userResults], Response::HTTP_OK);

    }

    /**
     * @Route(path="", name="post_result", methods={Request::METHOD_POST})
     * @param Request $request
     * @return Response
     */
    public function postResult(Request $request): Response
    {

        $dataRequest = $request->getContent();
        $data = json_decode($dataRequest, true);

        if (!isset($data['result']) || !isset($data['user_id']) || !isset($data['time'])) {
            return $this->error422();
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->findOneBy(["id" => $data['user_id']]);

        if (null == $user) {
            $msg = [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'User Id Not Found',
            ];

            return new JsonResponse(
                $msg, 400
            );
        }


        try {
            $result = new Result(
                $data['result'],
                $user,
                new \DateTime($data['time']) ?? new \DateTime('now')
            );

            $em->persist($result);
            $em->flush();

        } catch (\Exception $e) {
            echo "Caught Exception: ", $e->getMessage(), "\n";
            exit(0);
        }


        return new JsonResponse(
            ["result" => $result], Response::HTTP_CREATED
        );

    }

    /**
     * @Route(path="/{id}", name="delete_result", methods={Request::METHOD_DELETE})
     * @param Result|null $result
     * @return Response
     */
    public function deleteResult(?Result $result = null): Response
    {
        if (null == $result) {
            return $this->error404();
        }

        try {
            $em = $this->getDoctrine()->getManager();
            $em->remove($result);
            $em->flush();

        } catch (\Exception $e) {
            echo "Caught Exception: ", $e->getMessage(), "\n";
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route(path="/{id}", name="update_result", methods={Request::METHOD_PUT})
     * @param Request $request
     * @param Result|null $result
     * @return JsonResponse
     * @throws \Exception
     */
    public function updateResult(Request $request, ?Result $result): JsonResponse
    {
        if (null == $result) {
            return $this->error404();
        }

        $dataRequest = $request->getContent();
        $data = json_decode($dataRequest, true);
        $em = $this->getDoctrine()->getManager();

        // minimum one field must be sent
        if (array_count_values($data) < 1) {
            return $this->error422();
        }

        if (isset($data['user_id'])) {

            /** @var User $user */
            $user = $em->getRepository(User::class)->findOneBy(["id" => $data['user_id']]);

            if (null == $user) {

                $msg = [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'User id not found',
                ];

                return new JsonResponse(
                    $msg, 400
                );

            } else {
                $result->setUserId($user);
            }
        }


        if (isset($data['result'])) {
            $result->setResult($data['result']);
        }

        if (isset($data['time'])) {
            $result->setTime(new \DateTime($data['time']));
        }

        try {
            $em->merge($result);
            $em->flush();
        } catch (Exception $e) {
            echo "Caught Exception: ", $e->getMessage(), "\n";
        }

        return new JsonResponse(
            ["result" => $result], Response::HTTP_ACCEPTED
        );


    }

    /**
     * @Route(path="", name="options_results", methods={ Request::METHOD_OPTIONS })
     * @return Response
     * @codeCoverageIgnore
     */
    public function optionsResults(): Response
    {
        /** @var array $options */
        $options = "GET,POST";
        return new JsonResponse([],Response::HTTP_OK ,["Allow" => $options]);
    }


    /**
     * @Route(path="/{id}", name="options_result_unique", methods={ Request::METHOD_OPTIONS })
     * @return Response
     * @codeCoverageIgnore
     */
    public function optionsUniqueResult(): Response
    {
        /** @var array $options */
        $options="GET,UPDATE, DELETE";
        return new JsonResponse([],Response::HTTP_OK ,["Allow" => $options]);
    }


    //    ERRORS METHODS

    /**
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function error404(): JsonResponse
    {
        $msg = [
            'code' => 404,
            'message' => 'Not Found',
        ];

        return new JsonResponse(
            $msg, 404
        );
    }

    /**
     * genera una respuesta 422 - Unprocessable entity
     * @codeCoverageIgnore
     * @return JsonResponse
     */
    private function error422(): JsonResponse
    {
        $msg = [
            'code' => 422,
            'message' => 'There must contains at least parameter'
        ];

        return new JsonResponse(
            $msg, 422
        );
    }

}
