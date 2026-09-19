-- Express (event-day) registrations and open-link guests do not give a country.
ALTER TABLE registrations MODIFY country VARCHAR(80) NULL;
