<?php

namespace App\Form\DataMapper;

use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;

class ClientDataMapper implements DataMapperInterface
{
    /**
     * @param Client $viewData
     * @param FormInterface[]|\Traversable $forms
     */
    public function mapDataToForms($viewData, $forms): void
    {
        // there is no data yet, so nothing to prepopulate
        if (null === $viewData) {
            return;
        }

        // invalid data type
        if (!$viewData instanceof Client) {
            throw new UnexpectedTypeException($viewData, Client::class);
        }

        $forms = iterator_to_array($forms);

        $forms['identifier']->setData($viewData->getIdentifier());
        $forms['redirectUris']->setData(implode(' ', $viewData->getRedirectUris()));
        $forms['grants']->setData($viewData->getGrants());
        $forms['scopes']->setData($viewData->getScopes());
        $forms['active']->setData($viewData->isActive());
    }

    /**
     * @param FormInterface[]|\Traversable $forms
     * @param Client $viewData
     */
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);

        // array_filter in default mode to remove empty values
        /** @var RedirectUri[] $redirectUris */
        $redirectUris = array_map(static function (string $redirectUri): RedirectUri {
            return new RedirectUri($redirectUri);
        }, array_filter(explode(' ', $forms['redirectUris']->getData())));
        $grants = array_map(static function (string $grant): Grant {
            return new Grant($grant);
        }, $forms['grants']->getData());
        $scopes = array_map(static function (string $scope): Scope {
            return new Scope($scope);
        }, $forms['scopes']->getData());

        $viewData->setRedirectUris(...$redirectUris);
        $viewData->setGrants(...$grants);
        $viewData->setScopes(...$scopes);
        $viewData->setActive($forms['active']->getData());
    }
}
