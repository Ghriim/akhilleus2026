import { Navigate, Route, Routes } from 'react-router-dom';
import { LoginPage } from '@/features/auth/LoginPage';
import { AppLayout } from '@/layout/AppLayout';
import { ProtectedRoute } from './ProtectedRoute';
import { EquipmentListPage } from '@/features/equipments/EquipmentListPage';
import { EquipmentCreatePage } from '@/features/equipments/EquipmentCreatePage';
import { EquipmentEditPage } from '@/features/equipments/EquipmentEditPage';
import { MuscleListPage } from '@/features/muscles/MuscleListPage';
import { MuscleCreatePage } from '@/features/muscles/MuscleCreatePage';
import { MuscleEditPage } from '@/features/muscles/MuscleEditPage';
import { MovementListPage } from '@/features/movements/MovementListPage';
import { MovementCreatePage } from '@/features/movements/MovementCreatePage';
import { MovementEditPage } from '@/features/movements/MovementEditPage';
import { LevelBracketListPage } from '@/features/levelBrackets/LevelBracketListPage';
import { LevelBracketCreatePage } from '@/features/levelBrackets/LevelBracketCreatePage';
import { LevelBracketEditPage } from '@/features/levelBrackets/LevelBracketEditPage';
import { QuestListPage } from '@/features/quests/QuestListPage';
import { QuestCreatePage } from '@/features/quests/QuestCreatePage';
import { QuestEditPage } from '@/features/quests/QuestEditPage';

export const AppRouter = () => (
  <Routes>
    <Route path="/login" element={<LoginPage />} />
    <Route
      path="/"
      element={
        <ProtectedRoute>
          <AppLayout />
        </ProtectedRoute>
      }
    >
      <Route index element={<Navigate to="/equipments" replace />} />
      <Route path="equipments">
        <Route index element={<EquipmentListPage />} />
        <Route path="new" element={<EquipmentCreatePage />} />
        <Route path=":id" element={<EquipmentEditPage />} />
      </Route>
      <Route path="muscles">
        <Route index element={<MuscleListPage />} />
        <Route path="new" element={<MuscleCreatePage />} />
        <Route path=":id" element={<MuscleEditPage />} />
      </Route>
      <Route path="movements">
        <Route index element={<MovementListPage />} />
        <Route path="new" element={<MovementCreatePage />} />
        <Route path=":id" element={<MovementEditPage />} />
      </Route>
      <Route path="level-brackets">
        <Route index element={<LevelBracketListPage />} />
        <Route path="new" element={<LevelBracketCreatePage />} />
        <Route path=":id" element={<LevelBracketEditPage />} />
      </Route>
      <Route path="quests">
        <Route index element={<QuestListPage />} />
        <Route path="new" element={<QuestCreatePage />} />
        <Route path=":id" element={<QuestEditPage />} />
      </Route>
    </Route>
    <Route path="*" element={<Navigate to="/" replace />} />
  </Routes>
);
