
### Database setup ###

CREATE USER 'typo3'@'localhost' IDENTIFIED BY 'joh316';
GRANT ALL PRIVILEGES ON  `typo3\_%` . * TO  'typo3'@'localhost';
FLUSH PRIVILEGES;