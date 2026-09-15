// Evaluate in an authenticated student dashboard browser tab. All fixtures stay in memory.
(async () => {
    const assert = (ok, message) => { if (!ok) throw new Error(message); };
    const element = id => document.getElementById(id);
    const originalFetch = window.fetch;
    const originalDraw = window.drawLineChart;
    const originalFillText = CanvasRenderingContext2D.prototype.fillText;
    let draws = 0;
    let fail = false;
    const data = {
        profile: { full_name: 'Dashboard Test', overall_progress: 0, completed_lessons: 0, total_lessons: 0, systems_explored: 0, avg_quiz_score: null, quizzes_taken: 0, quizzes_passed: 0 },
        systems: [{ system_name: 'Skeletal System', completed_lessons: 0, total_lessons: 0, pct: 0 }],
        next_lesson: null, upcoming_assessments: [], achievements: []
    };
    let submissions = [];
    window.fetch = async (url, options) => {
        if (String(url).endsWith('/dashboard/student-stats.php')) return Response.json({ success: !fail, data });
        if (String(url).endsWith('/scores.php')) return Response.json({ success: !fail, data: { submissions } });
        return originalFetch(url, options);
    };
    window.drawLineChart = (...args) => { draws++; return originalDraw(...args); };
    CanvasRenderingContext2D.prototype.fillText = function (text, ...args) {
        assert(!/NaN|Infinity/.test(String(text)), 'Chart drew an invalid numeric label');
        return originalFillText.call(this, text, ...args);
    };
    const render = () => Promise.all([loadStudentDashboard(), loadRecentScoresAndChart()]);
    try {
        await render();
        assert(element('continueLearningGrid').textContent.includes('No lessons available yet'), 'Zero lessons must not claim completion');
        assert(element('systemProgressGrid').textContent.includes('No lessons available yet'), 'Empty system must not show a completion ratio');
        assert(element('scoreChartContainer').hidden && element('scoreChartEmpty').textContent.includes('No scores yet'), 'Empty scores need a message, not a chart');
        assert(draws === 0 && _cachedScoreData === null, 'Empty scores must not draw or cache chart data');
        assert(element('recentScoresTable').textContent.includes('No quiz submissions'), 'Empty results table');
        for (const id of ['achievementList', 'continueLearningGrid']) {
            const parent = element(id), message = parent.querySelector('.dashboard-empty');
            assert(message && message.getBoundingClientRect().width >= parent.clientWidth - 2, `${id} message must span the grid`);
        }

        data.profile.total_lessons = 2;
        data.profile.completed_lessons = 2;
        await loadStudentDashboard();
        assert(element('continueLearningGrid').textContent.includes('All published lessons are complete'), 'Completed lessons state');
        data.profile.completed_lessons = 1;
        await loadStudentDashboard();
        assert(!element('continueLearningGrid').textContent.includes('are complete'), 'Missing recommendation must not imply completion');

        data.next_lesson = { title: 'Learn the skeleton', module_title: 'Bones', system_name: 'Skeletal', progress_status: 'in_progress', duration_mins: 10 };
        data.achievements = [{ name: 'First lesson', earned: true }, { name: 'Quiz expert', earned: false }];
        data.systems[0] = { system_name: 'Skeletal System', completed_lessons: 1, total_lessons: 2, pct: 50 };
        const submission = { assessment_title: 'Anatomy quiz', assessment_type: 'quiz', system_name: 'Skeletal', score: 80, passed: true, submitted_at: '2026-09-15' };
        submissions = [submission, { ...submission, score: 0, passed: false }];
        await render();
        assert(element('continueLearningGrid').querySelector('.lesson-card'), 'Recommendation card retained');
        assert(element('achievementList').children.length === 2 && !element('achievementList').querySelector('.dashboard-empty'), 'Achievement badges retained');
        assert(element('systemProgressGrid').textContent.includes('1/2 (50%)'), 'Populated system progress retained');
        assert(!element('scoreChartContainer').hidden && element('scoreChartEmpty').hidden && draws > 0, 'Populated chart rendered');
        assert(_cachedScoreData.datasets[0].values.join(',') === '0,80', 'Real zero scores preserved in chart');
        assert(element('recentScoresTable').rows.length === 2, 'Recent results populated');

        submissions = [{ ...submission, score: null }, { ...submission, score: 'invalid' }];
        await loadRecentScoresAndChart();
        assert(element('scoreChartContainer').hidden && _cachedScoreData === null, 'Invalid scores cannot draw a chart');
        assert(!element('recentScoresTable').textContent.includes('NaN'), 'Invalid scores cannot render NaN in results');
        fail = true;
        await render();
        assert(element('scoreChartEmpty').textContent.includes('Unable to load'), 'Score errors are distinct from empty history');
        assert(element('continueLearningGrid').textContent.includes('Unable to load'), 'Dashboard error is distinct from completion');
        fail = false;
        submissions = [];
        data.profile.total_lessons = 0;
        data.profile.completed_lessons = 0;
        data.next_lesson = null;
        data.achievements = [];
        data.systems = [];
        await render();
        assert(element('systemProgressGrid').textContent.includes('No body systems'), 'No systems state');
        const beforeResize = draws;
        window.dispatchEvent(new Event('resize'));
        await new Promise(resolve => setTimeout(resolve, 300));
        assert(draws === beforeResize && element('scoreChartContainer').hidden, 'Resize must not restore an old chart');
        return 'Passed empty, completed, populated, invalid-score, API-error, recovery, and resize states.';
    } finally {
        window.fetch = originalFetch;
        window.drawLineChart = originalDraw;
        CanvasRenderingContext2D.prototype.fillText = originalFillText;
        await render();
    }
})()
