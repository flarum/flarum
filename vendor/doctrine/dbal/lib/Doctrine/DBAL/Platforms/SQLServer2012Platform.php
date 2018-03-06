<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Schema\Sequence;

/**
 * Platform to ensure compatibility of Doctrine with Microsoft SQL Server 2012 version.
 *
 * Differences to SQL Server 2008 and before are that sequences are introduced,
 * and support for the new OFFSET... FETCH syntax for result pagination has been added.
 *
 * @author Steve Müller <st.mueller@dzh-online.de>
 */
class SQLServer2012Platform extends SQLServer2008Platform
{
    /**
     * {@inheritdoc}
     */
    public function getAlterSequenceSQL(Sequence $sequence)
    {
        return 'ALTER SEQUENCE ' . $sequence->getQuotedName($this) .
               ' INCREMENT BY ' . $sequence->getAllocationSize();
    }

    /**
     * {@inheritdoc}
     */
    public function getCreateSequenceSQL(Sequence $sequence)
    {
        return 'CREATE SEQUENCE ' . $sequence->getQuotedName($this) .
               ' START WITH ' . $sequence->getInitialValue() .
               ' INCREMENT BY ' . $sequence->getAllocationSize() .
               ' MINVALUE ' . $sequence->getInitialValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getDropSequenceSQL($sequence)
    {
        if ($sequence instanceof Sequence) {
            $sequence = $sequence->getQuotedName($this);
        }

        return 'DROP SEQUENCE ' . $sequence;
    }

    /**
     * {@inheritdoc}
     */
    public function getListSequencesSQL($database)
    {
        return 'SELECT seq.name,
                       CAST(
                           seq.increment AS VARCHAR(MAX)
                       ) AS increment, -- CAST avoids driver error for sql_variant type
                       CAST(
                           seq.start_value AS VARCHAR(MAX)
                       ) AS start_value -- CAST avoids driver error for sql_variant type
                FROM   sys.sequences AS seq';
    }

    /**
     * {@inheritdoc}
     */
    public function getSequenceNextValSQL($sequenceName)
    {
        return 'SELECT NEXT VALUE FOR ' . $sequenceName;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsSequences()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * Returns Microsoft SQL Server 2012 specific keywords class
     */
    protected function getReservedKeywordsClass()
    {
        return Keywords\SQLServer2012Keywords::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function doModifyLimitQuery($query, $limit, $offset = null)
    {
        if ($limit === null && $offset === null) {
            return $query;
        }

        // Queries using OFFSET... FETCH MUST have an ORDER BY clause
        // Find the position of the last instance of ORDER BY and ensure it is not within a parenthetical statement
        // but can be in a newline
        $matches = array();
        $matchesCount = preg_match_all("/[\\s]+order\\s+by\\s/im", $query, $matches, PREG_OFFSET_CAPTURE);
        $orderByPos = false;
        if ($matchesCount > 0) {
            $orderByPos = $matches[0][($matchesCount - 1)][1];
        }

        if ($orderByPos === false
            || substr_count($query, "(", $orderByPos) - substr_count($query, ")", $orderByPos)
        ) {
            if (preg_match('/^SELECT\s+DISTINCT/im', $query)) {
                // SQL Server won't let us order by a non-selected column in a DISTINCT query,
                // so we have to do this madness. This says, order by the first column in the
                // result. SQL Server's docs say that a nonordered query's result order is non-
                // deterministic anyway, so this won't do anything that a bunch of update and
                // deletes to the table wouldn't do anyway.
                $query .= " ORDER BY 1";
            } else {
                // In another DBMS, we could do ORDER BY 0, but SQL Server gets angry if you
                // use constant expressions in the order by list.
                $query .= " ORDER BY (SELECT 0)";
            }
        }

        if ($offset === null) {
            $offset = 0;
        }

        // This looks somewhat like MYSQL, but limit/offset are in inverse positions
        // Supposedly SQL:2008 core standard.
        // Per TSQL spec, FETCH NEXT n ROWS ONLY is not valid without OFFSET n ROWS.
        $query .= " OFFSET " . (int) $offset . " ROWS";

        if ($limit !== null) {
            $query .= " FETCH NEXT " . (int) $limit . " ROWS ONLY";
        }

        return $query;
    }
}
