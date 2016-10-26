# Change Log
All notable changes to this project will be documented in this file.
Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [Unreleased][unreleased]
### Added

### Changed

### Fixed

### Removed

## [1.1.0]
### Fixed
 - Removed samsonasik/package-versions  - doesn't work well when globally installed - refs: https://github.com/Ocramius/PackageVersions/issues/38
   using manual version numbers for now. Hopefully can be bought back in when fixed.
 
### Added
 - Improved error reporting when installing extensions - gives hints when PHP extensions are missing (#19)
 - Required PHP extensions to composer.json (#19)

## [1.0.1]
### Fixed
 - Do not require a dev version of symfony/console (#22)
 
### Changed
 - Application::getHelp() no longer depends on parent (#21)

### Removed

## [1.0.0]
### Added
 - Ability to install via composer (#20)
