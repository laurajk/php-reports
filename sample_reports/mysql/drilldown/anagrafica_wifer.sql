-- Anagrafica WifER
-- This is a drilldown report that lists all orders for a given customer
-- VARIABLE: { name: "id", display: "id_wifer" }
-- TYPE: Pdo

SELECT
    id_wifer as `Id wifer`,
    matricola as `Matricola`,
    versione_software as `Versione Software`,
    numero_sim as `numero_sim`
FROM
    wifer_anagrafica
WHERE
    id_wifer = "{{ id }}"
