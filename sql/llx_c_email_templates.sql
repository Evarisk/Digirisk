INSERT INTO llx_c_email_templates (entity,module,type_template,lang,private,fk_user,datec,label,position,enabled,active,topic,joinfiles,content,content_lines)
VALUES (1,null,'Digirisk_LegalDisplay','',0,null,null,'Digirisk_LegalDisplay',1,1,1,'[__[MAIN_INFO_SOCIETE_NOM]__] - __(SendLegalDisplay)__',1,
concat('Bonjour,'
, 0xd0a, 0xd0a,
'Voici l’affichage légal généré en ce jour'
, 0xd0a, 0xd0a,
'Bien cordialement,'
, 0xd0a, 0xd0a,
'Service administratif', 0xd0a,
'__MYCOMPANY_FULLADDRESS__', 0xd0a,
'__MYCOMPANY_EMAIL__')
,null),

(1,null,'Digirisk_InformationsSharing','',0,null,null,'Digirisk_InformationsSharing',1,1,1,'[__[MAIN_INFO_SOCIETE_NOM]__] - __(SendInformationsSharing)__',1,
concat('Bonjour,'
, 0xd0a, 0xd0a,
'Voici la diffusion d’informations générée en ce jour'
, 0xd0a, 0xd0a,
'Bien cordialement,'
, 0xd0a, 0xd0a,
'Service administratif', 0xd0a,
'__MYCOMPANY_FULLADDRESS__', 0xd0a,
'__MYCOMPANY_EMAIL__')
,null)
