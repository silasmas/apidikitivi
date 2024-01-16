<?php
/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Notifications Language Lines
    |--------------------------------------------------------------------------
    |
    */

    // ===== ERROR PAGES
    // 400
    '400_title' => 'Mauvaise requête',
    '400_description' => 'Vérifiez votre requête s\'il vous plait !',
    // 401
    '401_title' => 'Non autorisé',
    '401_description' => 'Vous n\'avez pas d\'autorisation pour cette requête.',
    // 403
    '403_title' => 'Espace interdit',
    '403_description' => 'Cet espace n\'est pas permis.',
    // 404
    '404_title' => 'Page non trouvée',
    '404_description' => 'La page que vous cherchez n\'existe pas',
    // 405
    '405_title' => 'Méthode non permise',
    '405_description' => 'Votre requête est envoyée avec une mauvaise méthode.',
    // 419
    '419_title' => 'Page expirée',
    '419_description' => 'La page a mis longtemps sans activité.',
    // 500
    '500_title' => 'Erreur interne',
    '500_description' => 'Notre serveur rencontre un problème. Veuillez réessayez après quelques minutes s\'il vous plait !',
    // Others
    'expects_json' => 'La requête actuelle attend probablement une réponse JSON.',

    // ===== ALERTS
    'no_record' => 'Il n\'y a aucun enregistrement !',
    'create_error' => 'La création a échoué !',
    'update_error' => 'La modification a échoué !',
    // LegalInfoSubject
    'find_all_legal_info_subjects_success' => 'Sujets trouvés',
    'find_legal_info_subject_success' => 'Sujet trouvé',
    'find_legal_info_subject_404' => 'Sujet non trouvé',
    'create_legal_info_subject_success' => 'Sujet créé',
    'update_legal_info_subject_success' => 'Sujet modifié',
    'delete_legal_info_subject_success' => 'Sujet supprimé',
    // LegalInfoTitle
    'find_all_legal_info_titles_success' => 'Titres trouvés',
    'find_legal_info_title_success' => 'Titre trouvé',
    'find_legal_info_title_404' => 'Titre non trouvé',
    'create_legal_info_title_success' => 'Titre créé',
    'update_legal_info_title_success' => 'Titre modifié',
    'delete_legal_info_title_success' => 'Titre supprimé',
    // LegalInfoContent
    'find_all_legal_info_contents_success' => 'Contenus trouvés',
    'find_legal_info_content_success' => 'Contenu trouvé',
    'find_legal_info_content_404' => 'Contenu non trouvé',
    'create_legal_info_content_success' => 'Contenu créé',
    'update_legal_info_content_success' => 'Contenu modifié',
    'delete_legal_info_content_success' => 'Contenu supprimé',
    // Image
    'find_all_files_success' => 'Images trouvées',
    'find_file_success' => 'Image trouvée',
    'find_file_404' => 'Image non trouvée',
    'create_file_success' => 'Image créée',
    'update_file_success' => 'Image modifiée',
    'delete_file_success' => 'Image supprimée',
    // Group
    'find_all_groups_success' => 'Groupes trouvés',
    'find_group_success' => 'Groupe trouvé',
    'find_group_404' => 'Groupe non trouvé',
    'create_group_success' => 'Groupe créé',
    'update_group_success' => 'Groupe modifié',
    'delete_group_success' => 'Groupe supprimé',
    // Status
    'find_all_statuses_success' => 'Etats trouvés',
    'find_status_success' => 'Etat trouvé',
    'find_status_404' => 'Etat non trouvé',
    'create_status_success' => 'Etat créé',
    'update_status_success' => 'Etat modifié',
    'delete_status_success' => 'Etat supprimé',
    // Type
    'find_all_types_success' => 'Types trouvés',
    'find_type_success' => 'Type trouvé',
    'find_type_404' => 'Type non trouvé',
    'create_type_success' => 'Type créé',
    'update_type_success' => 'Type modifié',
    'delete_type_success' => 'Type supprimé',
    // Country
    'find_all_countries_success' => 'Pays trouvés',
    'find_country_success' => 'Pays trouvé',
    'find_country_404' => 'Pays non trouvé',
    'create_country_success' => 'Pays créé',
    'update_country_success' => 'Pays modifié',
    'delete_country_success' => 'Pays supprimé',
    // Address
    'find_all_addresses_success' => 'Adresses trouvées',
    'find_address_success' => 'Adresse trouvée',
    'find_address_404' => 'Adresse non trouvée',
    'create_address_success' => 'Adresse créée',
    'update_address_success' => 'Adresse modifiée',
    'delete_address_success' => 'Adresse supprimée',
    // Role
    'find_all_roles_success' => 'Rôles trouvés',
    'find_role_success' => 'Rôle trouvé',
    'find_role_404' => 'Rôle non trouvé',
    'create_role_success' => 'Rôle créé',
    'update_role_success' => 'Rôle modifié',
    'delete_role_success' => 'Rôle supprimé',
    // User
    'find_all_users_success' => 'Utilisateurs trouvés',
    'find_user_success' => 'Utilisateur trouvé',
    'find_api_token_success' => 'Jeton d\'API trouvé',
    'find_user_404' => 'Utilisateur non trouvé',
    'create_user_success' => 'Utilisateur créé',
    'create_user_SMS_failed' => 'Il y a un problème avec le service des SMS',
    'update_user_success' => 'Utilisateur modifié',
    'update_password_success' => 'Mot de passe modifié',
    'confirm_password_error' => 'Veuillez confirmer votre mot de passe',
    'confirm_new_password' => 'Veuillez confirmer le nouveau mot de passe',
    'delete_user_success' => 'Utilisateur supprimé',
    // RoleUser
    'find_all_role_users_success' => 'Rôles avec utilisateurs trouvés',
    'find_role_user_success' => 'Rôle avec utilisateur trouvé',
    'find_role_user_404' => 'Rôle avec utilisateur non trouvé',
    'create_role_user_success' => 'Rôle avec utilisateur créé',
    'update_role_user_success' => 'Rôle avec utilisateur modifié',
    'delete_role_user_success' => 'Rôle avec utilisateur supprimé',
    // PasswordReset
    'find_all_password_resets_success' => 'Réinitialisations des mots de passe trouvées',
    'find_password_reset_success' => 'Réinitialisation de mot de passe trouvée',
    'find_password_reset_404' => 'Réinitialisation de mot de passe non trouvée',
    'create_password_reset_success' => 'Réinitialisation de mot de passe créée',
    'update_password_reset_success' => 'Réinitialisation de mot de passe modifiée',
    'delete_password_reset_success' => 'Réinitialisation de mot de passe supprimée',
    // Message
    'find_all_messages_success' => 'Messages trouvés',
    'find_message_success' => 'Message trouvé',
    'find_message_404' => 'Message non trouvé',
    'create_message_success' => 'Message créé',
    'update_message_success' => 'Message modifié',
    'delete_message_success' => 'Message supprimé',
    // Notification
    'find_all_notifications_success' => 'Notifications trouvées',
    'find_notification_success' => 'Notification trouvée',
    'find_notification_404' => 'Notification non trouvée',
    'create_notification_success' => 'Notification créée',
    'update_notification_success' => 'Notification modifiée',
    'delete_notification_success' => 'Notification supprimée',
    // News
    'find_all_news_success' => 'Actualités trouvées',
    'find_news_success' => 'Actualité trouvée',
    'find_news_404' => 'Actualité non trouvée',
    'create_news_success' => 'Actualité créée',
    'update_news_success' => 'Actualité modifiée',
    'delete_news_success' => 'Actualité supprimée',
    // Offer
    'find_all_offers_success' => 'Offres trouvées',
    'find_offer_success' => 'Offre trouvée',
    'find_offer_404' => 'Offre non trouvée',
    'create_offer_success' => 'Offre créée',
    'update_offer_success' => 'Offre modifiée',
    'delete_offer_success' => 'Offre supprimée',
    // Payment
    'find_all_payments_success' => 'Paiements trouvés',
    'find_payment_success' => 'Paiement trouvé',
    'find_payment_404' => 'Paiement non trouvé',
    'processing_succeed' => 'Votre transaction a réussie. Vous pouvez la voir à la liste de vos paiements.',
    'error_while_processing' => 'Une erreur lors du traitement de votre requête',
    'process_failed' => 'Impossible de traiter la demande, veuillez réessayer',
    'process_canceled' => 'Vous avez annulé votre transaction',
    'create_payment_success' => 'Paiement créé',
    'update_payment_success' => 'Paiement modifié',
    'delete_payment_success' => 'Paiement supprimé',

    // ===== PLATFORM NOTIFICATIONS
    'welcome_user' => 'Bienvenue sur l\'application ACR ! Veuillez lire nos conditions avant de commencer.',
    'welcome_member' => 'Merci de faire confiance au parti ACR. Nous vous notifierons dès que votre adhésion sera approuvé.',
    'subscribed_to_party' => 'a souscrit au parti.',
    'member_joined' => 'Votre demande d\'adhésion est approuvée. Veuillez lire nos réglements et nos conditions d\'utilisations.',
    'member_locked' => 'Votre compte a été bloqué. Si vous avez des questions, contactez-nous via le n° de téléphone affiché sur notre site web.',
    'party_published' => 'Le parti a publié',
    'party_changed' => 'Le parti a modifié',
];
