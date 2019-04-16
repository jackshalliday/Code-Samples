class Group
  include Neo4j::ActiveNode
  include Neo4j::Timestamps

  property :name, type: String, constraint: :unique
  validates :name, presence: true, uniqueness: true

  has_many :in, :users, type: :member_of, model_class: :User
end
