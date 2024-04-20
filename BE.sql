
CREATE TABLE utilisateur (
    id_utilisateur SERIAL PRIMARY KEY,
    pseudo VARCHAR(255),
    mot_de_passe VARCHAR(255)
);

CREATE TABLE Infrastructure (
    id_infrastructure SERIAL PRIMARY KEY,
    nom VARCHAR(255),
    id_utilisateur INTEGER REFERENCES utilisateur(id_utilisateur)
);

CREATE TABLE réseau (
    id_reseau SERIAL PRIMARY KEY,
    nom VARCHAR(255),
    mask_reseau VARCHAR(255),
    adresse_réseau VARCHAR(255),
    id_infrastructure INTEGER REFERENCES Infrastructure(id_infrastructure)
);

CREATE TABLE sous_réseau (
    id_sous_reseau SERIAL PRIMARY KEY,
    mask VARCHAR(255),
    id_reseau INTEGER REFERENCES réseau(id_reseau)
);

CREATE TABLE Pc (
    id_pc SERIAL PRIMARY KEY,
    IP VARCHAR(255),
    Mac VARCHAR(255),
    id_reseau INTEGER REFERENCES réseau(id_reseau)
);

CREATE TABLE Routeur (
    id_routeur SERIAL PRIMARY KEY,
    Mac VARCHAR(255)
);

CREATE TABLE Elements (
    id_elements SERIAL PRIMARY KEY,
    IP_Source VARCHAR(255),
    IP_destination VARCHAR(255),
    interface_relayage VARCHAR(255),
    masque_destination VARCHAR(255),
    MTU INTEGER,
    id_pc INTEGER REFERENCES Pc(id_pc),
    id_routeur INTEGER REFERENCES Routeur(id_routeur)
);

CREATE TABLE Paquet (
    id_paquet SERIAL PRIMARY KEY,
    adresse_IP_source VARCHAR(255),
    adresse_IP_destination VARCHAR(255),
    TTL INTEGER,
    DF BOOLEAN,
    MF BOOLEAN,
    déplacement INTEGER,
    taille INTEGER,
    id_pc INTEGER REFERENCES Pc(id_pc)
);

CREATE TABLE connecter (
    id_reseau INTEGER REFERENCES réseau(id_reseau),
    id_routeur INTEGER REFERENCES Routeur(id_routeur),
    interface_routeur VARCHAR(255),
    PRIMARY KEY (id_reseau, id_routeur)
);

CREATE TABLE connecter2 (
    id_reseau INTEGER REFERENCES réseau(id_reseau),
    id_routeur INTEGER REFERENCES Routeur(id_routeur),
    interface_routeur_SR VARCHAR(255),
    PRIMARY KEY (id_reseau, id_routeur)
);

CREATE TABLE connecter3 (
    id_routeur INTEGER REFERENCES Routeur(id_routeur),
    id_routeur1 INTEGER REFERENCES Routeur(id_routeur),
    interface_routeur VARCHAR(255),
    PRIMARY KEY (id_routeur, id_routeur1)
);
