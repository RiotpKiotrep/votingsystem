
<?php

    return[
        'client_public' => hex2bin("ce4ddb4ac70feb390b29722f70adf06ba346920db3baef804f9514a87eb35c13"),
        'server_secret' => hex2bin("c13f4d014046f5f572a1edd938f2b8b2765c922611c7136dde463db32e9d4995")
    ];

    # key generation for the purpose of showcase - should be deleted in real version
    /*
    $cli_keypair = sodium_crypto_box_keypair();
    $cli_secret = sodium_crypto_box_secretkey($cli_keypair);
    print(bin2hex($cli_secret));
    print('\n');
    $cli_public = sodium_crypto_box_publickey($cli_keypair);
    print(bin2hex($cli_public));
    print('\n');

    
    $srv_keypair = sodium_crypto_box_keypair();
    $srv_secret = sodium_crypto_box_secretkey($srv_keypair);
    print(bin2hex($srv_secret));
    print('\n');
    $srv_public = sodium_crypto_box_publickey($srv_keypair);
    print(bin2hex($srv_public));
    print('\n');
    */
    
    #$client_secret = hex2bin("5b589e9aa4919025f075a46130d4aa77d35b62698269f3f6c1f07c644d7f499f");
    #$client_public = hex2bin("ce4ddb4ac70feb390b29722f70adf06ba346920db3baef804f9514a87eb35c13"); // <- do usunięcia i przechowania
    #$server_secret = hex2bin("c13f4d014046f5f572a1edd938f2b8b2765c922611c7136dde463db32e9d4995"); // <- w pliku administratora
    #$server_public = hex2bin("5f9b367104d8ed5e88fa4e410fe529678d55520a1469392dc447a8d41f4ccf49");
    #$sign_secret = hex2bin("0415d868857d885da551db06ab83adb41ba37df64493eddafafe14846554d117f87f6ae489c20fa4c706a8490dd0d07d9fa13a699ca4d8389de3594eadf54740");
    #$sign_public = hex2bin("f87f6ae489c20fa4c706a8490dd0d07d9fa13a699ca4d8389de3594eadf54740");

    //$sender_keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey($client_secret, $server_public);
    //$nonce = \random_bytes(\SODIUM_CRYPTO_BOX_NONCEBYTES);
    
    //$candidate_encr = sodium_crypto_box($candidate.'|'.$_SERVER['HTTP_REFERER'], $nonce, $sender_keypair);
