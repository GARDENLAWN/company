# GardenLawn Company Module Documentation

This document provides an overview of the GardenLawn Company module.

## Directory Structure

- **Api**: Interfaces for the module.
- **Block**: Block classes.
- **Console**: Console commands.
- **Controller**: Controller actions.
- **Cron**: Cron jobs.
- **Helper**: Helper classes.
- **Model**: Models and resource models.
- **Ui**: UI components.
- **etc**: Configuration files.
- **view**: Layouts and templates.

## Overview

This module manages company-related functionalities within the GardenLawn project.

---

# GardenLawn Company Module

Moduł odpowiedzialny za zarządzanie danymi firm (dealerów) oraz ich importem.

## Polecenia konsoli (CLI)

### Import Dealerów
Importuje dane dealerów z plików JSON (Stihl, Husqvarna) zlokalizowanych w `GardenLawn/Core/Configs/`.
Komenda sprawdza istnienie rekordu po nazwie i grupie klienta - jeśli istnieje, aktualizuje go, w przeciwnym razie tworzy nowy.

**Użycie:**
```bash
bin/magento gardenlawn:import:dealers
```
