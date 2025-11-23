CREATE DATABASE INFO_CULTURA;

USE INFO_CULTURA;

-- Tabela de usuários
CREATE TABLE
    usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        tipo_usuario ENUM ('comum', 'membro_neabi') NOT NULL,
        senha VARCHAR(60) NOT NULL /* Armazena hash da senha */
    );

-- Tabela de núcleos
CREATE TABLE
    nucleos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(191) NOT NULL UNIQUE,
        descricao TEXT DEFAULT NULL
    );

-- Tabela de calendário cultural (único calendário)
CREATE TABLE
    calendario (id INT AUTO_INCREMENT PRIMARY KEY);

-- Tabela de eventos culturais
CREATE TABLE
    eventos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome_evento VARCHAR(255) NOT NULL,
        descricao_evento TEXT NOT NULL,
        data_hora DATETIME NOT NULL,
        local_evento VARCHAR(255) NOT NULL,
        banner MEDIUMBLOB DEFAULT NULL COMMENT 'Imagem binária do banner do evento',
        resultados_impacto TEXT DEFAULT NULL COMMENT 'Resumo dos resultados do evento, se aplicável',
        status_evento TINYINT (1) DEFAULT 0 COMMENT 'Indica se foi aprovado por super admin',
        id_usuario INT NOT NULL COMMENT 'Usuário que cadastrou o evento',
        id_nucleo INT NOT NULL COMMENT 'Núcleo responsável pelo evento',
        id_calendario INT NOT NULL COMMENT 'Relacionamento com o calendário cultural',
        id_evento_original INT DEFAULT NULL COMMENT 'Se for um evento passado, pode referenciar o evento futuro original',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios (id),
        FOREIGN KEY (id_nucleo) REFERENCES nucleos (id),
        FOREIGN KEY (id_calendario) REFERENCES calendario (id),
        FOREIGN KEY (id_evento_original) REFERENCES eventos (id)
    );

-- Tabela para armazenar múltiplas fotos por evento
CREATE TABLE
    fotos_evento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_evento INT NOT NULL,
        foto LONGBLOB NOT NULL COMMENT 'Imagem binária da foto do evento',
        descricao VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (id_evento) REFERENCES eventos (id)
    );

-- Tabela de datas culturais importantes
CREATE TABLE
    datas_culturais (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome_data VARCHAR(255) NOT NULL,
        descricao TEXT,
        data_numero DATE NOT NULL,
        importancia TEXT,
        imagem MEDIUMBLOB DEFAULT NULL COMMENT 'Imagem binária relacionada à data cultural',
        id_calendario INT NOT NULL,
        FOREIGN KEY (id_calendario) REFERENCES calendario (id)
    );

-- Tabela para registro de aprovações/rejeições de eventos
CREATE TABLE
    aprovacao_eventos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_evento INT NOT NULL,
        status_eventos TINYINT (1) NOT NULL COMMENT 'TRUE para aprovado, FALSE para rejeitado',
        comentario TEXT DEFAULT NULL,
        data_aprovacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_evento) REFERENCES eventos (id)
    );

-- Tabela de super administradores
CREATE TABLE
    super_adm (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(191) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL /* Armazena hash da senha */
    );