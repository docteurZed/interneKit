Fichiers JSON — Informations pratiques des CHU

Fichiers :
- consultations.json : spécialités, sites, jours et horaires.
- actes_sante.json : catalogue unique des actes et examens, avec établissement, catégorie et prix.
- etablissements.json : établissements et informations pratiques.

Règles :
- Les tarifs de biologie et d’imagerie du CHU-Campus sont aussi appliqués au CHU-SO, selon la précision de l’utilisateur.
- prix = null signifie que le prix n’est pas indiqué.
- prix = 0 reprend une valeur « 00 » de la source et doit être vérifié : cela ne signifie pas automatiquement gratuit.
- Les horaires et prix sont issus du PDF fourni et doivent être confirmés auprès des services concernés.
