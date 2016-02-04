<?php
/*
 * This file is part of the login-cidadao project or it's bundles.
 *
 * (c) Guilherme Donato <guilhermednt on github>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LoginCidadao\OAuthBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use League\Uri\Schemes\Http as HttpUri;

class DomainOwnershipValidator extends ConstraintValidator
{

    public function validate($organization, Constraint $constraint)
    {
        if (!$organization->getValidationUrl()) {
            $organization->checkValidation();
            return;
        }

        $validationCode = $organization->getValidationSecret();
        $validationUrl  = $organization->getValidationUrl();
        $domain         = $organization->getDomain();

        $url = $this->checkUrl($validationUrl, $domain, $validationCode);

        if ($url === false) {
            return;
        }

        $organization->checkValidation();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $organization->getValidationUrl());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);

        if (strstr($response, $organization->getValidationSecret()) === false) {
            $this->buildUrlViolation('organizations.validation.error.code_not_found');
        }

        $organization->setVerifiedAt(new \DateTime());
        $organization->setValidatedUrl($organization->getValidationUrl());
    }

    private function checkUrl($validationUrl, $domain, $validationCode)
    {
        $uri = HttpUri::createFromString($validationUrl);

        $response = $uri->__toString();

        if (strstr($response, urlencode($validationCode)) !== false) {
            $this->buildUrlViolation('organizations.validation.error.code_in_url');
            $response = false;
        }

        if ($uri->getHost() !== $domain) {
            $this->buildUrlViolation('organizations.validation.error.invalid_domain');
            $response = false;
        }

        if ($uri->getQuery()) {
            $this->buildUrlViolation('organizations.validation.error.query_string');
            $response = false;
        }

        return $response;
    }

    private function buildUrlViolation($message)
    {
        $this->context->buildViolation($message)
            ->atPath('validationUrl')
            ->addViolation();
    }
}
