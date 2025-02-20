contao.id/contao-bundle
==========================

# Installation

## By Contao Manager
See https://extensions.contao.org/?p=contao-id%2Fcontao-bundle

## By Composer

`composer require contao-id/contao-bundle`

## Configuration

### `.env.local`

Copy the configuration values (provided by the **contao.id** application backend) and add them to the `.env.local`:

⚠️ If you don't already have a `.env` file, create one and add it there.

```
# contao.id
CONTAO_ID_IDENTIFIER=56b2f1405da206fc08896dc2b26506db
CONTAO_ID_SECRET=20794a680517dc01abfca8d8c134569e9f56ac34bdd6b4f8da656321731181c39f7a4378602fef43b313b23d9c523f1007c8acab2e26141bb748a4d664256ebe
```
![config-dialog](https://github.com/user-attachments/assets/5d2fcdf8-815f-49ae-81d7-59b7c98d6a41)

### `config/security.yaml`

```
contao_backend:
    [...]

    entry_point: contao_login

    oauth:
        resource_owners:
            contao_id: "/contao/login/contao_id"
        login_path: /contao/login
        default_target_path: /contao
        use_forward: false
        failure_path: /contao/login

        oauth_user_provider:
            service: contao_id_contao.security.user_provider
```

### `config/packages/hwi_oauth.yaml`

```
hwi_oauth:
    firewall_names: [contao_backend]

    resource_owners:
        contao_id:
            type:                oauth2
            class:               HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\OAuth2ResourceOwner
            client_id:           '%contao_id_identifier%'
            client_secret:       '%contao_id_secret%'
            access_token_url:    'https://auth.contao.id/auth/token'
            authorization_url:   'https://auth.contao.id/auth/authorize'
            infos_url:           'https://auth.contao.id/api/auth/info/%contao_id_identifier%'
            scope:               'read'
            user_response_class: HWI\Bundle\OAuthBundle\OAuth\Response\PathUserResponse
            paths:
                identifier: id
```

### `config/bundles.php`

```
HWI\Bundle\OAuthBundle\HWIOAuthBundle::class => ['all' => true],
ContaoId\ContaoBundle\ContaoIdContaoBundle::class => ['all' => true],
```

### `config/routes.yaml`

```
[...]
ContaoIdContaoBundle:
    resource: "@ContaoIdContaoBundle/config/routes.yaml"
```
