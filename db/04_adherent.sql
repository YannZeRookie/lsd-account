-- Fix the spelling mistake of Adherents
-- adherant -> adherent

UPDATE lsd_roles SET role='adherent' WHERE role='adherant';
