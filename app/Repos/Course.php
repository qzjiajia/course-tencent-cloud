<?php

namespace App\Repos;

use App\Library\Paginator\Adapter\QueryBuilder as PagerQueryBuilder;
use App\Models\Category as CategoryModel;
use App\Models\Chapter as ChapterModel;
use App\Models\ChapterUser as ChapterUserModel;
use App\Models\Comment as CommentModel;
use App\Models\Consult as ConsultModel;
use App\Models\ConsultLike as ConsultLikeModel;
use App\Models\Course as CourseModel;
use App\Models\CourseCategory as CourseCategoryModel;
use App\Models\CourseFavorite as CourseFavoriteModel;
use App\Models\CoursePackage as CoursePackageModel;
use App\Models\CourseRelated as CourseRelatedModel;
use App\Models\CourseUser as CourseUserModel;
use App\Models\Package as PackageModel;
use App\Models\Review as ReviewModel;
use App\Models\ReviewLike as ReviewLikeModel;
use App\Models\User as UserModel;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Resultset;
use Phalcon\Mvc\Model\ResultsetInterface;

class Course extends Repository
{

    public function paginate($where = [], $sort = 'latest', $page = 1, $limit = 15)
    {
        $builder = $this->modelsManager->createBuilder();

        $builder->from(CourseModel::class);

        $builder->where('1 = 1');

        if (!empty($where['category_id'])) {
            $where['id'] = $this->getCategoryCourseIds($where['category_id']);
        } elseif (!empty($where['teacher_id'])) {
            $where['id'] = $this->getTeacherCourseIds($where['teacher_id']);
        }

        if (!empty($where['id'])) {
            if (is_array($where['id'])) {
                $builder->inWhere('id', $where['id']);
            } else {
                $builder->andWhere('id = :id:', ['id' => $where['id']]);
            }
        }

        if (!empty($where['title'])) {
            $builder->andWhere('title LIKE :title:', ['title' => "%{$where['title']}%"]);
        }

        if (!empty($where['model'])) {
            $builder->andWhere('model = :model:', ['model' => $where['model']]);
        }

        if (!empty($where['level'])) {
            $builder->andWhere('level = :level:', ['level' => $where['level']]);
        }

        if (isset($where['free'])) {
            if ($where['free'] == 1) {
                $builder->andWhere('market_price = 0');
            } else {
                $builder->andWhere('market_price > 0');
            }
        }

        if (isset($where['published'])) {
            $builder->andWhere('published = :published:', ['published' => $where['published']]);
        }

        if (isset($where['deleted'])) {
            $builder->andWhere('deleted = :deleted:', ['deleted' => $where['deleted']]);
        }

        if ($sort == 'free') {
            $builder->andWhere('market_price = 0');
        } elseif ($sort == 'vip') {
            $builder->andWhere('vip_price < market_price');
        } elseif ($sort == 'vip_free') {
            $builder->andWhere('vip_price = 0');
        }

        switch ($sort) {
            case 'score':
                $orderBy = 'score DESC';
                break;
            case 'rating':
                $orderBy = 'rating DESC';
                break;
            case 'popular':
                $orderBy = 'user_count DESC';
                break;
            default:
                $orderBy = 'id DESC';
                break;
        }

        $builder->orderBy($orderBy);

        $pager = new PagerQueryBuilder([
            'builder' => $builder,
            'page' => $page,
            'limit' => $limit,
        ]);

        return $pager->paginate();
    }

    /**
     * @param int $id
     * @return CourseModel|Model|bool
     */
    public function findById($id)
    {
        return CourseModel::findFirst($id);
    }

    /**
     * @param array $ids
     * @param array|string $columns
     * @return ResultsetInterface|Resultset|CourseModel[]
     */
    public function findByIds($ids, $columns = '*')
    {
        return CourseModel::query()
            ->columns($columns)
            ->inWhere('id', $ids)
            ->execute();
    }

    /**
     * @param int $courseId
     * @return ResultsetInterface|Resultset|UserModel[]
     */
    public function findTeachers($courseId)
    {
        $roleType = CourseUserModel::ROLE_TEACHER;

        return $this->modelsManager->createBuilder()
            ->columns('u.*')
            ->addFrom(UserModel::class, 'u')
            ->join(CourseUserModel::class, 'u.id = cu.user_id', 'cu')
            ->where('cu.course_id = :course_id:', ['course_id' => $courseId])
            ->andWhere('cu.role_type = :role_type:', ['role_type' => $roleType])
            ->andWhere('u.deleted = 0')
            ->getQuery()->execute();
    }

    /**
     * @param int $courseId
     * @return ResultsetInterface|Resultset|CategoryModel[]
     */
    public function findCategories($courseId)
    {
        return $this->modelsManager->createBuilder()
            ->columns('c.*')
            ->addFrom(CategoryModel::class, 'c')
            ->join(CourseCategoryModel::class, 'c.id = cc.category_id', 'cc')
            ->where('cc.course_id = :course_id:', ['course_id' => $courseId])
            ->andWhere('c.deleted = 0')
            ->getQuery()->execute();
    }

    /**
     * @param int $courseId
     * @return ResultsetInterface|Resultset|PackageModel[]
     */
    public function findPackages($courseId)
    {
        return $this->modelsManager->createBuilder()
            ->columns('p.*')
            ->addFrom(PackageModel::class, 'p')
            ->join(CoursePackageModel::class, 'p.id = cp.package_id', 'cp')
            ->where('cp.course_id = :course_id:', ['course_id' => $courseId])
            ->andWhere('p.deleted = 0')
            ->getQuery()->execute();
    }

    /**
     * @param int $courseId
     * @return ResultsetInterface|Resultset|CourseModel[]
     */
    public function findRelatedCourses($courseId)
    {
        return $this->modelsManager->createBuilder()
            ->columns('c.*')
            ->addFrom(CourseModel::class, 'c')
            ->join(CourseRelatedModel::class, 'c.id = cr.related_id', 'cr')
            ->where('cr.course_id = :course_id:', ['course_id' => $courseId])
            ->andWhere('c.deleted = 0')
            ->getQuery()->execute();
    }

    /**
     * @param int $courseId
     * @return ResultsetInterface|Resultset|ChapterModel[]
     */
    public function findChapters($courseId)
    {
        return ChapterModel::query()
            ->where('course_id = :course_id:', ['course_id' => $courseId])
            ->andWhere('deleted = 0')
            ->execute();
    }

    /**
     * @param int $courseId
     * @return ResultsetInterface|Resultset|ChapterModel[]
     */
    public function findLessons($courseId)
    {
        return ChapterModel::query()
            ->where('course_id = :course_id:', ['course_id' => $courseId])
            ->andWhere('parent_id > 0')
            ->andWhere('deleted = 0')
            ->execute();
    }

    /**
     * @param int $courseId
     * @param int $userId
     * @param int $planId
     * @return ResultsetInterface|Resultset|ChapterUserModel[]
     */
    public function findUserLearnings($courseId, $userId, $planId)
    {
        return ChapterUserModel::query()
            ->where('course_id = :course_id:', ['course_id' => $courseId])
            ->andWhere('user_id = :user_id:', ['user_id' => $userId])
            ->andWhere('plan_id = :plan_id:', ['plan_id' => $planId])
            ->execute();
    }

    /**
     * @param int $courseId
     * @param int $userId
     * @return ResultsetInterface|Resultset|ConsultLikeModel[]
     */
    public function findUserConsultLikes($courseId, $userId)
    {
        return $this->modelsManager->createBuilder()
            ->columns('cv.*')
            ->addFrom(ConsultModel::class, 'c')
            ->join(ConsultLikeModel::class, 'c.id = cv.consult_id', 'cv')
            ->where('c.course_id = :course_id:', ['course_id' => $courseId])
            ->andWhere('cv.user_id = :user_id:', ['user_id' => $userId])
            ->getQuery()->execute();
    }

    /**
     * @param int $courseId
     * @param int $userId
     * @return ResultsetInterface|Resultset|ReviewLikeModel[]
     */
    public function findUserReviewLikes($courseId, $userId)
    {
        return $this->modelsManager->createBuilder()
            ->columns('rv.*')
            ->addFrom(ReviewModel::class, 'r')
            ->join(ReviewLikeModel::class, 'r.id = rv.review_id', 'rv')
            ->where('r.course_id = :course_id:', ['course_id' => $courseId])
            ->andWhere('rv.user_id = :user_id:', ['user_id' => $userId])
            ->getQuery()->execute();
    }

    public function countLessons($courseId)
    {
        return ChapterModel::count([
            'conditions' => 'course_id = :course_id: AND parent_id > 0 AND deleted = 0',
            'bind' => ['course_id' => $courseId],
        ]);
    }

    public function countPackages($courseId)
    {
        return CoursePackageModel::count([
            'conditions' => 'course_id = :course_id:',
            'bind' => ['course_id' => $courseId],
        ]);
    }

    public function countUsers($courseId)
    {
        return CourseUserModel::count([
            'conditions' => 'course_id = :course_id: AND deleted = 0',
            'bind' => ['course_id' => $courseId],
        ]);
    }

    public function countConsults($courseId)
    {
        return ConsultModel::count([
            'conditions' => 'course_id = :course_id: AND published = 1',
            'bind' => ['course_id' => $courseId],
        ]);
    }

    public function countReviews($courseId)
    {
        return ReviewModel::count([
            'conditions' => 'course_id = :course_id: AND published = 1',
            'bind' => ['course_id' => $courseId],
        ]);
    }

    public function countComments($courseId)
    {
        return CommentModel::count([
            'conditions' => 'course_id = :course_id: AND published = 1',
            'bind' => ['course_id' => $courseId],
        ]);
    }

    public function countFavorites($courseId)
    {
        return CourseFavoriteModel::count([
            'conditions' => 'course_id = :course_id: AND published = 1',
            'bind' => ['course_id' => $courseId],
        ]);
    }

    public function averageRating($courseId)
    {
        return ReviewModel::average([
            'column' => 'rating',
            'conditions' => 'course_id = :course_id: AND published = 1',
            'bind' => ['course_id' => $courseId],
        ]);
    }

    protected function getCategoryCourseIds($categoryId)
    {
        $categoryIds = is_array($categoryId) ? $categoryId : [$categoryId];

        $repo = new CourseCategory();

        $rows = $repo->findByCategoryIds($categoryIds);

        if ($rows->count() == 0) {
            return [];
        }

        return kg_array_column($rows->toArray(), 'course_id');
    }

    protected function getTeacherCourseIds($teacherId)
    {
        $teacherIds = is_array($teacherId) ? $teacherId : [$teacherId];

        $repo = new CourseUser();

        $rows = $repo->findByTeacherIds($teacherIds);

        if ($rows->count() == 0) {
            return [];
        }

        return kg_array_column($rows->toArray(), 'course_id');
    }

}
