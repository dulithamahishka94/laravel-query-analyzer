<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\DTOs\ComplexityAnalysis;
use GladeHQ\QueryLens\DTOs\ComplexityLevel;
use GladeHQ\QueryLens\DTOs\PerformanceRating;
use GladeHQ\QueryLens\DTOs\QueryAnalysisResult;
use GladeHQ\QueryLens\DTOs\QueryType;
use GladeHQ\QueryLens\QueryAnalyzer;
use GladeHQ\QueryLens\Tests\Fakes\InMemoryQueryStorage;
use PHPUnit\Framework\TestCase;

class DTOTest extends TestCase
{
    // ---------------------------------------------------------------
    // QueryType enum
    // ---------------------------------------------------------------

    public function test_query_type_from_sql_detects_select(): void
    {
        $this->assertSame(QueryType::SELECT, QueryType::fromSql('SELECT * FROM users'));
    }

    public function test_query_type_from_sql_detects_insert(): void
    {
        $this->assertSame(QueryType::INSERT, QueryType::fromSql('INSERT INTO users (name) VALUES ("a")'));
    }

    public function test_query_type_from_sql_detects_update(): void
    {
        $this->assertSame(QueryType::UPDATE, QueryType::fromSql('UPDATE users SET name = "b"'));
    }

    public function test_query_type_from_sql_detects_delete(): void
    {
        $this->assertSame(QueryType::DELETE, QueryType::fromSql('DELETE FROM users WHERE id = 1'));
    }

    public function test_query_type_from_sql_detects_create(): void
    {
        $this->assertSame(QueryType::CREATE, QueryType::fromSql('CREATE TABLE users (id INT)'));
    }

    public function test_query_type_from_sql_detects_alter(): void
    {
        $this->assertSame(QueryType::ALTER, QueryType::fromSql('ALTER TABLE users ADD COLUMN age INT'));
    }

    public function test_query_type_from_sql_detects_drop(): void
    {
        $this->assertSame(QueryType::DROP, QueryType::fromSql('DROP TABLE users'));
    }

    public function test_query_type_from_sql_returns_other_for_unknown(): void
    {
        $this->assertSame(QueryType::OTHER, QueryType::fromSql('SHOW TABLES'));
    }

    public function test_query_type_from_sql_is_case_insensitive(): void
    {
        $this->assertSame(QueryType::SELECT, QueryType::fromSql('select * from users'));
    }

    public function test_query_type_from_sql_trims_whitespace(): void
    {
        $this->assertSame(QueryType::SELECT, QueryType::fromSql('   SELECT * FROM users'));
    }

    public function test_query_type_from_sql_handles_empty_string(): void
    {
        $this->assertSame(QueryType::OTHER, QueryType::fromSql(''));
    }

    public function test_query_type_value_is_uppercase_string(): void
    {
        $this->assertSame('SELECT', QueryType::SELECT->value);
        $this->assertSame('OTHER', QueryType::OTHER->value);
    }

    // ---------------------------------------------------------------
    // ComplexityLevel enum
    // ---------------------------------------------------------------

    public function test_complexity_level_from_score_low(): void
    {
        $this->assertSame(ComplexityLevel::LOW, ComplexityLevel::fromScore(0));
        $this->assertSame(ComplexityLevel::LOW, ComplexityLevel::fromScore(5));
    }

    public function test_complexity_level_from_score_medium(): void
    {
        $this->assertSame(ComplexityLevel::MEDIUM, ComplexityLevel::fromScore(6));
        $this->assertSame(ComplexityLevel::MEDIUM, ComplexityLevel::fromScore(10));
    }

    public function test_complexity_level_from_score_high(): void
    {
        $this->assertSame(ComplexityLevel::HIGH, ComplexityLevel::fromScore(11));
        $this->assertSame(ComplexityLevel::HIGH, ComplexityLevel::fromScore(100));
    }

    // ---------------------------------------------------------------
    // ComplexityAnalysis DTO
    // ---------------------------------------------------------------

    public function test_complexity_analysis_simple_query(): void
    {
        $result = ComplexityAnalysis::analyze('SELECT id FROM users');

        $this->assertSame(0, $result->score);
        $this->assertSame(ComplexityLevel::LOW, $result->level);
        $this->assertSame(0, $result->joins);
        $this->assertSame(0, $result->subqueries);
        $this->assertSame(0, $result->conditions);
    }

    public function test_complexity_analysis_with_joins_and_conditions(): void
    {
        $sql = 'SELECT u.* FROM users u JOIN profiles p ON u.id = p.user_id WHERE u.active = 1';
        $result = ComplexityAnalysis::analyze($sql);

        $this->assertSame(1, $result->joins);
        $this->assertSame(1, $result->conditions);
        $this->assertSame(0, $result->subqueries);
        // score = 1*2 + 0*3 + 1*1 = 3
        $this->assertSame(3, $result->score);
        $this->assertSame(ComplexityLevel::LOW, $result->level);
    }

    public function test_complexity_analysis_with_subquery(): void
    {
        $sql = 'SELECT * FROM users WHERE id IN (SELECT user_id FROM orders)';
        $result = ComplexityAnalysis::analyze($sql);

        $this->assertSame(1, $result->subqueries);
        $this->assertSame(1, $result->conditions); // WHERE
    }

    public function test_complexity_analysis_high_complexity(): void
    {
        // 4 JOINs * 2 = 8, 1 subquery * 3 = 3, 2 conditions (WHERE + HAVING) = 2, ORDER BY = 1, GROUP BY = 1 = 15
        $sql = 'SELECT * FROM users u JOIN profiles p ON u.id = p.user_id JOIN orders o ON u.id = o.user_id JOIN items i ON o.id = i.order_id JOIN tags t ON i.id = t.item_id WHERE u.id IN (SELECT id FROM admins) HAVING COUNT(*) > 1 ORDER BY u.name GROUP BY u.id';
        $result = ComplexityAnalysis::analyze($sql);

        $this->assertSame(ComplexityLevel::HIGH, $result->level);
        $this->assertGreaterThan(10, $result->score);
    }

    public function test_complexity_analysis_custom_weights(): void
    {
        $sql = 'SELECT * FROM users u JOIN profiles p ON u.id = p.user_id';
        $result = ComplexityAnalysis::analyze($sql, ['joins' => 10]);

        // 1 join * 10 = 10, score should be >= 10
        $this->assertGreaterThanOrEqual(10, $result->score);
    }

    public function test_complexity_analysis_to_array(): void
    {
        $result = ComplexityAnalysis::analyze('SELECT id FROM users');
        $array = $result->toArray();

        $this->assertArrayHasKey('score', $array);
        $this->assertArrayHasKey('level', $array);
        $this->assertArrayHasKey('joins', $array);
        $this->assertArrayHasKey('subqueries', $array);
        $this->assertArrayHasKey('conditions', $array);
        $this->assertSame('low', $array['level']);
    }

    public function test_complexity_analysis_empty_sql(): void
    {
        $result = ComplexityAnalysis::analyze('');

        $this->assertSame(0, $result->score);
        $this->assertSame(ComplexityLevel::LOW, $result->level);
    }

    public function test_complexity_analysis_includes_order_by_and_group_by_in_score(): void
    {
        $sql = 'SELECT * FROM users ORDER BY name GROUP BY department';
        $result = ComplexityAnalysis::analyze($sql);

        // ORDER BY = 1, GROUP BY = 1, so at least 2 for those
        $this->assertGreaterThanOrEqual(2, $result->score);
    }

    public function test_complexity_analysis_subquery_count_never_negative(): void
    {
        // INSERT has no SELECT, so subquery count should be 0, not -1
        $result = ComplexityAnalysis::analyze('INSERT INTO users (name) VALUES ("test")');

        $this->assertSame(0, $result->subqueries);
    }

    // ---------------------------------------------------------------
    // PerformanceRating enum
    // ---------------------------------------------------------------

    public function test_performance_rating_fast(): void
    {
        $rating = PerformanceRating::fromTime(0.05);

        $this->assertSame(PerformanceRating::FAST, $rating);
        $this->assertFalse($rating->isSlow());
    }

    public function test_performance_rating_moderate(): void
    {
        $rating = PerformanceRating::fromTime(0.3);

        $this->assertSame(PerformanceRating::MODERATE, $rating);
        $this->assertFalse($rating->isSlow());
    }

    public function test_performance_rating_slow(): void
    {
        $rating = PerformanceRating::fromTime(0.8);

        $this->assertSame(PerformanceRating::SLOW, $rating);
        $this->assertFalse($rating->isSlow());
    }

    public function test_performance_rating_very_slow(): void
    {
        $rating = PerformanceRating::fromTime(1.5);

        $this->assertSame(PerformanceRating::VERY_SLOW, $rating);
        $this->assertTrue($rating->isSlow());
    }

    public function test_performance_rating_custom_thresholds(): void
    {
        $thresholds = ['fast' => 0.01, 'moderate' => 0.05, 'slow' => 0.1];
        $rating = PerformanceRating::fromTime(0.08, $thresholds);

        $this->assertSame(PerformanceRating::SLOW, $rating);
    }

    public function test_performance_rating_to_array_includes_execution_time(): void
    {
        $rating = PerformanceRating::FAST;
        $array = $rating->toArray(0.05);

        $this->assertSame(0.05, $array['execution_time']);
        $this->assertSame('fast', $array['rating']);
        $this->assertFalse($array['is_slow']);
    }

    public function test_performance_rating_boundary_values(): void
    {
        // Exactly at threshold boundary should be fast (<=)
        $this->assertSame(PerformanceRating::FAST, PerformanceRating::fromTime(0.1));
        // Just above fast is moderate
        $this->assertSame(PerformanceRating::MODERATE, PerformanceRating::fromTime(0.100001));
        // Exactly at slow boundary
        $this->assertSame(PerformanceRating::SLOW, PerformanceRating::fromTime(1.0));
        // Just above slow
        $this->assertSame(PerformanceRating::VERY_SLOW, PerformanceRating::fromTime(1.000001));
    }

    public function test_performance_rating_defaults_when_thresholds_empty(): void
    {
        // Should use default thresholds when none provided
        $rating = PerformanceRating::fromTime(0.05, []);

        $this->assertSame(PerformanceRating::FAST, $rating);
    }

    // ---------------------------------------------------------------
    // QueryAnalysisResult DTO
    // ---------------------------------------------------------------

    public function test_analysis_result_to_array_structure(): void
    {
        $result = new QueryAnalysisResult(
            type: QueryType::SELECT,
            performance: PerformanceRating::FAST,
            executionTime: 0.05,
            complexity: ComplexityAnalysis::analyze('SELECT id FROM users'),
            recommendations: ['Use specific columns'],
            issues: [['type' => 'efficiency', 'message' => 'Consider adding index']],
        );

        $array = $result->toArray();

        $this->assertSame('SELECT', $array['type']);
        $this->assertSame('fast', $array['performance']['rating']);
        $this->assertSame(0.05, $array['performance']['execution_time']);
        $this->assertFalse($array['performance']['is_slow']);
        $this->assertArrayHasKey('score', $array['complexity']);
        $this->assertSame(['Use specific columns'], $array['recommendations']);
        $this->assertCount(1, $array['issues']);
    }

    public function test_analysis_result_with_empty_recommendations_and_issues(): void
    {
        $result = new QueryAnalysisResult(
            type: QueryType::INSERT,
            performance: PerformanceRating::FAST,
            executionTime: 0.01,
            complexity: ComplexityAnalysis::analyze('INSERT INTO t VALUES (1)'),
            recommendations: [],
            issues: [],
        );

        $array = $result->toArray();

        $this->assertSame([], $array['recommendations']);
        $this->assertSame([], $array['issues']);
    }

    // ---------------------------------------------------------------
    // Integration: QueryAnalyzer uses DTOs
    // ---------------------------------------------------------------

    public function test_analyzer_analyze_query_returns_array_with_dto_structure(): void
    {
        $analyzer = new QueryAnalyzer(
            ['performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0]],
            new InMemoryQueryStorage()
        );

        $result = $analyzer->analyzeQuery('SELECT * FROM users WHERE id = 1', [], 0.05);

        // Verify the structure matches what DTOs produce
        $this->assertSame('SELECT', $result['type']);
        $this->assertSame('fast', $result['performance']['rating']);
        $this->assertSame(0.05, $result['performance']['execution_time']);
        $this->assertFalse($result['performance']['is_slow']);
        $this->assertIsInt($result['complexity']['score']);
        $this->assertIsString($result['complexity']['level']);
        $this->assertIsArray($result['recommendations']);
        $this->assertIsArray($result['issues']);
    }

    public function test_analyzer_uses_config_complexity_weights(): void
    {
        $analyzer = new QueryAnalyzer(
            [
                'performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0],
                'complexity_weights' => ['joins' => 10, 'subqueries' => 3, 'conditions' => 1],
            ],
            new InMemoryQueryStorage()
        );

        $result = $analyzer->analyzeQuery('SELECT * FROM a JOIN b ON a.id = b.a_id');

        // With joins weight = 10, 1 join = 10 score, should be medium
        $this->assertSame('medium', $result['complexity']['level']);
    }
}
