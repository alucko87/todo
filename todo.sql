SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

DROP SCHEMA IF EXISTS `todo` ;
CREATE SCHEMA IF NOT EXISTS `todo` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `todo` ;

-- -----------------------------------------------------
-- Table `todo`.`users`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `todo`.`users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `pwd` VARCHAR(60) NOT NULL,
  `create_date` BIGINT UNSIGNED NOT NULL,
  `token` VARCHAR(60) NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `todo`.`projects`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `todo`.`projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `create_date` BIGINT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC),
  INDEX `fk_user_id_idx` (`user_id` ASC),
  CONSTRAINT `fk_user_id`
    FOREIGN KEY (`user_id`)
    REFERENCES `todo`.`users` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `todo`.`tasks`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `todo`.`tasks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NULL,
  `name` VARCHAR(10000) NOT NULL,
  `status` BINARY NOT NULL DEFAULT 0,
  `create_date` BIGINT UNSIGNED NOT NULL,
  `exp_date` BIGINT UNSIGNED NULL,
  `priority` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `position` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC),
  INDEX `fk_project_id_idx` (`project_id` ASC),
  CONSTRAINT `fk_project_id`
    FOREIGN KEY (`project_id`)
    REFERENCES `todo`.`projects` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

INSERT INTO users (name, pwd, create_date) VALUES
('admin',
'$2y$10$S973LjF1P3WEC.XAUx1xle6CHNdQWINLFuuVy44Kxm1wBUuitcOaO',
1488059788),
('user',
'$2y$10$SVZGU2wfr.x5E9zTakwjiuRcdHFWrw3IuDQ7az/v9ycNk7qtS8PHi',
1488060356);

INSERT INTO projects (name, create_date, user_id) VALUES
('Complete the test task for Ruby Garage', 1488070123, 2);

INSERT INTO tasks (project_id, name, create_date, exp_date) VALUES
(1, 'Open mock-up in Adobe Fireworks', 1488070250, 1488071670),
(1, 'Attentively check the file', 1488070350, 1488085670),
(1, 'Write HTML & CSS', 1488070550, 1488092370),
(1, 'Add Javascript', 1488070750, 1488112470);