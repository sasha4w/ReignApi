<?php

declare(strict_types=1);
/*
-------------------------------------------------------------------------------
les routes
-------------------------------------------------------------------------------
 */

return [
    ///// AUTH /////
    ['POST', '/ReignApi/api/v1/auth/login', 'auth@login'],
    ['POST', '/ReignApi/api/v1/auth/logout', 'auth@logout'],

    
    ///// ADMIN /////

    /// GET ///
       
    /// POST ///
    ['POST', '/ReignApi/api/v1/administrateur/login', 'administrateur@login'],

    /// PATCH ///
    
    /// DELETE ///

    ///// CREATEUR /////
    

    /// GET ///
       
    /// POST ///
    ['POST', '/ReignApi/api/v1/createur', 'createur@create'],
    ['POST', '/ReignApi/api/v1/createur/login', 'createur@login'],


    /// PATCH ///
    ['PATCH', '/ReignApi/api/v1/createurs/warn/{id_createur:\d+}', 'createur@warn'],

    /// DELETE ///

    ///// DECK /////

    /// GET ///
    
    ['GET', '/ReignApi/api/v1/decks', 'deck@getDecks'], 
    ['GET', '/ReignApi/api/v1/decks/playable', 'deck@getPlayableDecks'], 
    /// POST ///
    ['POST', '/ReignApi/api/v1/deck', 'deck@create'],

    /// PATCH ///
    ['PATCH', '/ReignApi/api/v1/decks/{id_deck:\d+}', 'deck@update'],
    ['PATCH', '/ReignApi/api/v1/decks/like/{id_deck:\d+}', 'deck@like'],

    /// DELETE ///
    ['DELETE', '/ReignApi/api/v1/decks/{id_deck:\d+}', 'deck@delete'],

    ///// CARTE /////

    /// GET ///
    ['GET', '/ReignApi/api/v1/cartes/administrateur', 'carte@getCartesForAdmin'], 
    ['GET', '/ReignApi/api/v1/cartes/createur/{id}', 'carte@getCartesForCreateur'],
    ['GET', '/ReignApi/api/v1/cartes/deck/{id_deck:\d+}', 'carte@getCartesForOneDeck'],
    
    /// POST ///
    ['POST', '/ReignApi/api/v1/carte', 'carte@create'],

    /// PATCH ///
    ['PATCH', '/ReignApi/api/v1/cartes/{id_carte:\d+}', 'carte@update'],

    
    /// DELETE ///
    ['DELETE', '/ReignApi/api/v1/cartes/{id_carte:\d+}', 'carte@delete'],


    ///// CARTE ALEATOIRE /////
    
    /// GET ///
       
    /// POST ///
    ['POST', '/ReignApi/api/v1/carte/aleatoire/deck/{deck_id}', 'carte@createOrGetRandom'],

    /// PATCH ///
    
    /// DELETE ///

    ///// CREATEUR BANNI /////
    
    /// GET ///
       
    /// POST ///

    /// PATCH ///
    
    /// DELETE ///

];
