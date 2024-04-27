CREATE TABLE utilisateur(
   id_utilisateur SERIAL,
   pseudo VARCHAR(255),
   mot_de_passe VARCHAR(255),
   PRIMARY KEY(id_utilisateur)
);

CREATE TABLE Infrastructure(
   id_infrastructure SERIAL,
   nom VARCHAR(255),
   id_utilisateur INT NOT NULL,
   PRIMARY KEY(id_infrastructure),
   FOREIGN KEY(id_utilisateur) REFERENCES utilisateur(id_utilisateur)
);

CREATE TABLE réseau(
   id_reseau SERIAL,
   nom VARCHAR(255),
   MTU INT NOT NULL,
   mask_reseau VARCHAR(255) NOT NULL,
   adresse_réseau VARCHAR(255),
   id_infrastructure INT NOT NULL,
   PRIMARY KEY(id_reseau),
   FOREIGN KEY(id_infrastructure) REFERENCES Infrastructure(id_infrastructure)
);

CREATE TABLE sous_réseau(
   id_sousréseau SERIAL,
   mask VARCHAR(255),
   IP_Sous_Reseau VARCHAR(255),
   id_reseau INT NOT NULL,
   PRIMARY KEY(id_sousréseau),
   FOREIGN KEY(id_reseau) REFERENCES réseau(id_reseau)
);

CREATE TABLE Pc(
   id_pc SERIAL,
   IP_Pc VARCHAR(255),
   id_sousréseau INT NOT NULL,
   PRIMARY KEY(id_pc),
   FOREIGN KEY(id_sousréseau) REFERENCES sous_réseau(id_sousréseau)
);

CREATE TABLE Routeur(
   id_routeur SERIAL,
   IP_Routeur VARCHAR(255) NOT NULL,
   MTU INT,
   PRIMARY KEY(id_routeur)
);

CREATE TABLE Elements(
   id_elements SERIAL,
   IP_destination VARCHAR(255),
   interface_relayage INT NOT NULL,
   masque_destination VARCHAR(255),
   MTU INT,
   PRIMARY KEY(id_elements)
);

CREATE TABLE Paquet(
   Id_Paquet SERIAL,
   adresse_IP_source VARCHAR(255),
   adresse_IP_destination VARCHAR(255),
   TTL INT,
   DF BOOLEAN,
   MF BOOLEAN,
   déplacement INT,
   taille INT,
   id_pc INT NOT NULL,
   PRIMARY KEY(Id_Paquet),
   FOREIGN KEY(id_pc) REFERENCES Pc(id_pc)
);

CREATE TABLE connecter_pc(
   id_pc INT,
   id_routeur INT,
   interface_routeur_pc INT NOT NULL,
   PRIMARY KEY(id_pc, id_routeur),
   FOREIGN KEY(id_pc) REFERENCES Pc(id_pc),
   FOREIGN KEY(id_routeur) REFERENCES Routeur(id_routeur)
);

CREATE TABLE elem_routeur(
   id_routeur INT,
   id_elements INT,
   PRIMARY KEY(id_routeur, id_elements),
   FOREIGN KEY(id_routeur) REFERENCES Routeur(id_routeur),
   FOREIGN KEY(id_elements) REFERENCES Elements(id_elements)
);

CREATE TABLE elem_pc(
   id_pc INT,
   id_elements INT,
   PRIMARY KEY(id_pc, id_elements),
   FOREIGN KEY(id_pc) REFERENCES Pc(id_pc),
   FOREIGN KEY(id_elements) REFERENCES Elements(id_elements)
);

CREATE TABLE connecter_routeur(
   id_routeur INT,
   id_routeur_1 INT,
   interface_routeur INT,
   PRIMARY KEY(id_routeur, id_routeur_1),
   FOREIGN KEY(id_routeur) REFERENCES Routeur(id_routeur),
   FOREIGN KEY(id_routeur_1) REFERENCES Routeur(id_routeur)
);