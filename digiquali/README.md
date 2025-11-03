--- DigiQuali ---
DigiQuali is used to managed control quality by the Dolibarr foundation.
This README contains specific information and tips around DigiQuali.

--- Sql request to extract a control with label of question, answers and tasks to do.
SELECT
	-- t.fk_question_group,
	ldq2.ref as Groupe,
	ldq2.label as 'Label Groupe',
	-- t.fk_question,
	ldq.ref as RefCheck,
	ldq.description,
	ldq.type,
	-- t.rowid,
	t.ref as RefResult,
	t.tms,
	t.answer,
	t.comment,
	t.status,
	count(dt.rowid) as tasks
FROM
	llx_digiquali_controldet as t
LEFT JOIN llx_digiquali_answer as da ON
	da.position = t.answer
	AND da.fk_question = t.fk_question
LEFT JOIN llx_element_element as dt ON
	dt.fk_source = t.rowid
	AND dt.sourcetype = 'controldet'
	And dt.targettype = 'project_task'
INNER JOIN llx_digiquali_question as ldq ON t.fk_question = ldq.rowid 
INNER JOIN llx_digiquali_questiongroup as ldq2 ON t.fk_question_group = ldq2.rowid 
WHERE
	1 = 1 and t.fk_control = 1
GROUP BY
	t.fk_question_group,
	ldq2.ref,
	t.fk_question,
	ldq.ref,
	ldq.description,
	ldq.type,
	t.rowid,
	t.ref,
	t.tms,
	t.answer,
	t.comment,
	t.status
ORDER BY
	t.rowid ASC
