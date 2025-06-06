import { useAtomValue } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { persistedHubsAtom } from '../../../state/shortcode.atoms';
import { GameAvatar } from '../../GameAvatar';

interface ShortcodeHubProps {
  hubId: number;
}

export const ShortcodeHub: FC<ShortcodeHubProps> = ({ hubId }) => {
  const { t } = useTranslation();

  const persistedHubs = useAtomValue(persistedHubsAtom);

  const foundHub = persistedHubs?.find((hub) => hub.id === hubId);

  if (!foundHub) {
    return null;
  }

  return (
    <span data-testid="hub-embed" className="inline">
      <GameAvatar
        id={foundHub.id}
        title={t('{{hubTitle}} (Hubs)', { hubTitle: foundHub.title })}
        dynamicTooltipType="hub"
        badgeUrl={foundHub.badgeUrl!}
        size={24}
        variant="inline"
        href={route('hub.show', { gameSet: foundHub })}
      />
    </span>
  );
};
