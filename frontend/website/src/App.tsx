import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider } from '@/auth/AuthContext';
import { ProtectedRoute } from '@/auth/ProtectedRoute';
import { AppLayout } from '@/components/layout/AppLayout';
import { LoginPage } from '@/pages/auth/LoginPage';
import { RegisterPage } from '@/pages/auth/RegisterPage';
import { DashboardPage } from '@/pages/workout/DashboardPage';
import { PlanningPage } from '@/pages/workout/PlanningPage';
import { HistoryPage } from '@/pages/workout/HistoryPage';
import { WorkoutNewPage } from '@/pages/workout/WorkoutNewPage';
import { WorkoutDetailsPage } from '@/pages/workout/WorkoutDetailsPage';
import { MovementsPage } from '@/pages/movement/MovementsPage';
import { XpJournalPage } from '@/pages/leveling/XpJournalPage';
import { UniqueQuestsPage } from '@/pages/quests/UniqueQuestsPage';
import { StatisticsPage } from '@/pages/statistics/StatisticsPage';
import { SettingsPage } from '@/pages/settings/SettingsPage';

export function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route element={<ProtectedRoute />}>
            <Route element={<AppLayout />}>
              <Route path="/" element={<DashboardPage />} />
              <Route path="/planning" element={<PlanningPage />} />
              <Route path="/history" element={<HistoryPage />} />
              <Route path="/workouts/new" element={<WorkoutNewPage />} />
              <Route path="/workouts/:id" element={<WorkoutDetailsPage />} />
              <Route path="/movements" element={<MovementsPage />} />
              <Route path="/achievements" element={<Navigate to="/movements" replace />} />
              <Route path="/leveling/journal" element={<XpJournalPage />} />
              <Route path="/quests/unique" element={<UniqueQuestsPage />} />
              <Route path="/statistics" element={<StatisticsPage />} />
              <Route path="/settings" element={<SettingsPage />} />
            </Route>
          </Route>
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}
