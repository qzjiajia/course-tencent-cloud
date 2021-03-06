<?php

namespace App\Models;

use Phalcon\Mvc\Model\Behavior\SoftDelete;

class Account extends Model
{

    /**
     * 主键编号
     *
     * @var int
     */
    public $id = 0;

    /**
     * 邮箱
     *
     * @var string
     */
    public $email = '';

    /**
     * 手机
     *
     * @var string
     */
    public $phone = '';

    /**
     * 密码
     *
     * @var string
     */
    public $password = '';

    /**
     * 密盐
     *
     * @var string
     */
    public $salt = '';

    /**
     * 删除标识
     *
     * @var int
     */
    public $deleted = 0;

    /**
     * 创建时间
     *
     * @var int
     */
    public $create_time = 0;

    /**
     * 更新时间
     *
     * @var int
     */
    public $update_time = 0;

    public function getSource(): string
    {
        return 'kg_account';
    }

    public function initialize()
    {
        parent::initialize();

        $this->addBehavior(
            new SoftDelete([
                'field' => 'deleted',
                'value' => 1,
            ])
        );
    }

    public function beforeCreate()
    {
        $this->create_time = time();
    }

    public function beforeUpdate()
    {
        $this->update_time = time();
    }

}