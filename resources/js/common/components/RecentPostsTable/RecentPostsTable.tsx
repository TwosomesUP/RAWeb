import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

import { DiffTimestamp } from '../DiffTimestamp';
import { InertiaLink } from '../InertiaLink';
import { RecentPostAggregateLinks } from '../RecentPostAggregateLinks';

interface RecentPostsTableProps {
  paginatedTopics: App.Data.PaginatedData<App.Data.ForumTopic>;

  showAdditionalPosts?: boolean;
  showLastPostBy?: boolean;
}

export const RecentPostsTable: FC<RecentPostsTableProps> = ({
  paginatedTopics,
  showAdditionalPosts = true,
  showLastPostBy = true,
}) => {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  return (
    <table className="table-highlight">
      <thead>
        <tr className="do-not-highlight">
          {showLastPostBy ? <th className="min-w-40">{t('Last Post By')}</th> : null}

          <th>{t('Message')}</th>

          {showAdditionalPosts ? (
            <th className="whitespace-nowrap text-right">{t('Additional Posts')}</th>
          ) : null}
        </tr>
      </thead>

      <tbody>
        {paginatedTopics.items.map((topic) => (
          <tr key={topic.latestComment?.id}>
            {showLastPostBy ? (
              <td className="py-3">
                {topic.latestComment?.user ? (
                  <UserAvatar {...topic.latestComment.user} size={24} />
                ) : null}
              </td>
            ) : null}

            <td className="py-2">
              <p className="flex items-center gap-x-2">
                <InertiaLink
                  href={
                    route('forum-topic.show', {
                      topic: topic.id,
                      comment: topic.latestComment?.id,
                    }) + `#${topic.latestComment?.id}`
                  }
                >
                  {topic.title}
                </InertiaLink>

                {topic.latestComment?.createdAt ? (
                  <span className="smalldate" data-testid="smalldate">
                    <DiffTimestamp
                      asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
                      at={topic.latestComment.createdAt}
                    />
                  </span>
                ) : null}
              </p>

              <div className="comment text-overflow-wrap">
                <p className="lg:line-clamp-2 xl:line-clamp-1">{topic.latestComment?.body}</p>
              </div>
            </td>

            {showAdditionalPosts ? (
              <td className="text-right">
                <RecentPostAggregateLinks topic={topic} />
              </td>
            ) : null}
          </tr>
        ))}
      </tbody>
    </table>
  );
};
