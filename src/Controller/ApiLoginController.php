<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Cassandra\Type\UserType;
use Doctrine\DBAL\Exception\DriverException;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authenticator\JsonLoginAuthenticator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ApiLoginController extends AbstractController
{
    private ValidatorInterface $validator;

    private UserRepository $userRepository;

    public function __construct(ValidatorInterface $validator, UserRepository $userRepository) {
        $this->validator = $validator;
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/", methods={"GET"})
     */
    public function index(): JsonResponse
    {
        return $this->json([]);
    }

    /**
     * @Route("/register", name="app_registration", methods={"POST"})
     */
    public function register(Request $request, UserPasswordHasherInterface $passwordEncoder): JsonResponse
    {
        try {
            $this->validateRequest($request);
        } catch (ValidationFailedException $validationFailedException) {
            /** @var ConstraintViolation[] $errors */
            $errors = $validationFailedException->getValue();

            $result = [];
            foreach ($errors as $error) {
                $result[$error->getPropertyPath()] = $error->getMessageTemplate();
            }
            return new JsonResponse(['errors' => $result], 400);
        }

        $data = $request->toArray();
        $user = new User();

        try {
            $password = $passwordEncoder->hashPassword($user, $data['password']);
            $user->setPassword($password);
            $user->setEmail($data['email']);

            $this->userRepository->add($user, true);

        } catch (DriverException $driverException) {
            return new JsonResponse(['errors' => ['user' => $driverException->getMessage()]], 400);
        }

        return $this->json([
            'user' => $user,
        ]);
    }

    /**
     * @Route("/login", name="app_login", methods={"POST"})
     */
    public function login(): JsonResponse
    {
        if ($this->isGranted(AuthenticatedVoter::IS_AUTHENTICATED_FULLY) === false) {
            return $this->json([
                'message' => 'wrong credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

          return $this->json([
              'user' => $this->getUser()->getUserIdentifier(),
          ]);
    }

    /**
     * @Route("/user", name="app_user", methods={"GET"})
     */
    public function getCurrentUser(): JsonResponse
    {
        return $this->json([
            'user' => $this->getUser() !== null ? $this->getUser()->getUserIdentifier() : null,
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new Exception('');
    }

    private function validateRequest(Request $request): void
    {
        $constraints = new Assert\Collection([
            'password' => [
                new Assert\NotBlank()
            ],
            'repeatPassword' => [
                new Assert\Optional()
            ],
            'email' => [
                new Assert\NotBlank(),
                new Assert\Email()
            ],
        ]);

        $errors = $this->validator->validate($request->toArray(), $constraints);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($errors->getIterator()->getArrayCopy(), $errors);
        }
    }

}
