🔐 Contao ID Contao Bundle
==========================

## `config/security.yaml`

```
contao_backend:
    [...]

    oauth:
        resource_owners:
            contao_id: "/contao/login/contao_id"
        login_path: /contao/login
        default_target_path: /contao
        use_forward: false
        failure_path: /contao/login

        oauth_user_provider:
            service: ContaoId\ContaoBundle\Security\UserProvider
```

## `config/packages/hwi_oauth.yaml`

```
hwi_oauth:
    firewall_names: [contao_backend]

    resource_owners:
        contao_id:
            type:                oauth2
            class:               HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\OAuth2ResourceOwner
            client_id:           '%contao_id_identifier%'
            client_secret:       '%contao_id_secret%'
            access_token_url:    'https://contao.id/auth/token'
            authorization_url:   'https://contao.id/auth/authorize'
            infos_url:           'https://contao.id/api/auth/info/%contao_id_identifier%'
            scope:               'read'
            user_response_class: HWI\Bundle\OAuthBundle\OAuth\Response\PathUserResponse
            paths:
                identifier: id
```

## `config/bundles.php`

```
HWI\Bundle\OAuthBundle\HWIOAuthBundle::class => ['all' => true],
ContaoId\ContaoBundle\ContaoIdContaoBundle::class => ['all' => true],
```

## `config/config.yaml`

```
# Auth
contao_id_identifier: 1234
contao_id_secret: 12345678
```

## `config/routes.yaml`

```
[...]
ContaoIdContaoBundle:
    resource: "@ContaoIdContaoBundle/config/routes.yaml"
```
