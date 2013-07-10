SMS16 library
================================

php API to SMS gate http://sms16.ru

Install via composer
--------------------------------
    "require": {
        "rumbu/sms16": "dev-master"
    }

Usage with [Symfony](https://github.com/symfony/symfony)
--------------------------------
0.  Create `Sms` folder in your bundle;

1.  Copy file `smsProxy.php` to this folder;

2.  Setup service in `services.yml`:

    <pre>
    sms.proxy:
        class: You\YourBundle\Sms\smsProxy
        arguments:
            login: %sms_login%
            password: %sms_password%
            sender: %sms_sender%
    </pre>

3.  Add parameters to parameters.yml

    <pre>
    parameters:
        sms_login: login
        sms_password: password
        sms_sender: SendeR
    </pre>

4. Send sms:

        $this->container->get('sms.proxy')->send('+Phone', 'Text', 'Sender');
