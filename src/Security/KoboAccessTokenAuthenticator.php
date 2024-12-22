<?php

namespace App\Security;

use App\Security\Badge\KoboDeviceBadge;
use App\Security\Token\PostAuthenticationTokenWithKoboDevice;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class KoboAccessTokenAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private readonly KoboAccessTokenHandlerInterface $accessTokenHandler,
        private readonly AccessTokenExtractorInterface $accessTokenExtractor,
        private readonly ?AuthenticationSuccessHandlerInterface $successHandler = null,
        private readonly ?AuthenticationFailureHandlerInterface $failureHandler = null,
    ) {
    }

    #[\Override]
    public function supports(Request $request): ?bool
    {
        return null === $this->accessTokenExtractor->extractAccessToken($request) ? false : null;
    }

    #[\Override]
    public function authenticate(Request $request): Passport
    {
        $accessToken = $this->accessTokenExtractor->extractAccessToken($request);
        if (null === $accessToken) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        $koboBadge = $this->accessTokenHandler->getKoboDeviceBadgeFrom($accessToken);

        $user = $koboBadge->getDevice()->getUser();

        $passeport = new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), fn () => $user));
        $passeport->addBadge($koboBadge);

        return $passeport;
    }

    #[\Override]
    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        $badge = $passport->hasBadge(KoboDeviceBadge::class) ? $passport->getBadge(KoboDeviceBadge::class) : null;

        if (!$badge instanceof BadgeInterface) {
            return new PostAuthenticationToken($passport->getUser(), $firewallName, $passport->getUser()->getRoles());
        }

        return new PostAuthenticationTokenWithKoboDevice($badge->getDevice(), $passport->getUser(), $firewallName, $passport->getUser()->getRoles());
    }

    #[\Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($token instanceof PostAuthenticationTokenWithKoboDevice) {
            $request->attributes->set(KoboTokenHandler::TOKEN_KOBO_ATTRIBUTE, $token->getKoboDevice());
        }

        return $this->successHandler?->onAuthenticationSuccess($request, $token);
    }

    #[\Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($this->failureHandler instanceof AuthenticationFailureHandlerInterface) {
            return $this->failureHandler->onAuthenticationFailure($request, $exception);
        }

        return new Response(
            null,
            Response::HTTP_UNAUTHORIZED,
        );
    }
}
